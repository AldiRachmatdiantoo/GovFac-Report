<?php

namespace App\Modules\Report\Services;

use App\Modules\Auth\Models\User;
use App\Modules\Report\Contracts\ReportStatusService;
use App\Modules\Report\Events\ReportSubmitted;
use App\Modules\Report\Models\Report;
use App\Modules\Report\Repositories\ReportRepository;
use App\Modules\Report\Repositories\TagRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class CreateReportService
{
    public function __construct(
        private ReportRepository $reports,
        private TagRepository $tags,
        private ReportCodeGenerator $codes,
        private ReportPhotoStorage $photos,
        private ReportStatusService $statuses,
    ) {}

    /** @param array{title: string, description: string, photos: array<UploadedFile>, tags: array<string>} $data */
    public function create(User $actor, array $data): Report
    {
        $disk = config('filesystems.default');
        $paths = [];

        try {
            $report = DB::transaction(function () use ($actor, $data, $disk, &$paths): Report {
                $report = $this->reports->create($actor, $data['title'], $data['description']);
                $this->reports->assignCode($report, $this->codes->generate($report->id, $report->created_at));

                foreach (array_values($data['photos']) as $order => $photo) {
                    $stored = $this->photos->store($photo, $report->id, $disk);
                    $paths[] = $stored['path'];
                    $this->reports->addPhoto($report, $stored, $order);
                }

                $tagIds = [];
                foreach (array_unique(array_map(fn (string $tag): string => mb_strtolower(ltrim($tag, '#')), $data['tags'])) as $name) {
                    $tagIds[] = $this->tags->firstOrCreate($name)->id;
                }
                $this->reports->syncTags($report, $tagIds);
                $this->statuses->initialize($report, $actor);

                return $this->reports->loadRelations($report);
            });
        } catch (Throwable $exception) {
            Storage::disk($disk)->delete($paths);
            throw $exception;
        }

        ReportSubmitted::dispatch($report);

        return $report;
    }
}
