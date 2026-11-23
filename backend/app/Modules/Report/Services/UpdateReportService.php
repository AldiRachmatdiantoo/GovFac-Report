<?php

namespace App\Modules\Report\Services;

use App\Modules\Auth\Models\User;
use App\Modules\Report\Contracts\ReportStatusService;
use App\Modules\Report\Events\ReportSubmitted;
use App\Modules\Report\Models\Report;
use App\Modules\Report\Repositories\ReportReadRepository;
use App\Modules\Report\Repositories\ReportRepository;
use App\Modules\Report\Repositories\ReportRevisionRepository;
use App\Modules\Report\Repositories\TagRepository;
use App\Modules\Shared\Enums\ReportStatus;
use App\Modules\Shared\Exceptions\InvalidStatusTransition;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class UpdateReportService
{
    public function __construct(
        private readonly ReportReadRepository $reads,
        private readonly ReportRepository $reports,
        private readonly ReportRevisionRepository $revisions,
        private readonly TagRepository $tags,
        private readonly ReportPhotoStorage $photos,
        private readonly ReportStatusService $statuses,
    ) {}

    /**
     * @param  array{title: string, description: string, photos?: array<UploadedFile>, tags: array<string>}  $data
     *
     * @throws InvalidStatusTransition when the report is not `rejected`
     */
    public function update(User $actor, Report $report, array $data): Report
    {
        $disk = config('filesystems.default');
        $newPaths = [];
        $oldPaths = [];

        try {
            $report = DB::transaction(function () use ($actor, $report, $data, $disk, &$newPaths, &$oldPaths): Report {
                $locked = $this->reads->lockById($report->getKey());

                if ($locked->status !== ReportStatus::Rejected) {
                    throw new InvalidStatusTransition(
                        $locked->status,
                        ReportStatus::AiValidation,
                        sprintf('Laporan berstatus %s tidak bisa diedit.', $locked->status->value),
                    );
                }

                $this->revisions->revise($locked, $data['title'], $data['description']);

                if (isset($data['photos'])) {
                    $oldPaths = $this->revisions->removePhotos($locked);

                    foreach (array_values($data['photos']) as $order => $photo) {
                        $stored = $this->photos->store($photo, $locked->getKey(), $disk);
                        $newPaths[] = $stored['path'];
                        $this->reports->addPhoto($locked, $stored, $order);
                    }
                }

                $tagIds = [];
                foreach (array_unique($data['tags']) as $name) {
                    $tagIds[] = $this->tags->firstOrCreate($name)->id;
                }
                $this->reports->syncTags($locked, $tagIds);

                $this->statuses->transition($locked, ReportStatus::AiValidation, $actor);

                return $this->reports->loadRelations($locked);
            });
        } catch (Throwable $exception) {
            Storage::disk($disk)->delete($newPaths);
            throw $exception;
        }

        Storage::disk($disk)->delete($oldPaths);

        // The edited revision goes through AI validation again (PBI-6).
        ReportSubmitted::dispatch($report);

        return $report;
    }
}
