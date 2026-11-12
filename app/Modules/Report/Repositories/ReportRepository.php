<?php

namespace App\Modules\Report\Repositories;

use App\Modules\Auth\Models\User;
use App\Modules\Report\Models\Report;
use App\Modules\Report\Models\ReportPhoto;
use App\Modules\Shared\Enums\ReportStatus;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ReportRepository
{
    public function create(User $actor, string $title, string $description): Report
    {
        $report = new Report(['reporter_id' => $actor->id, 'title' => $title, 'description' => $description, 'revision' => 1]);
        $report->status = ReportStatus::AiValidation;
        $report->save();

        return $report;
    }

    public function assignCode(Report $report, string $code): void
    {
        $report->code = $code;
        $report->save();
    }

    /** @param array{path: string, mime: string, size: int} $photo */
    public function addPhoto(Report $report, array $photo, int $sortOrder): ReportPhoto
    {
        return $report->photos()->create([...$photo, 'sort_order' => $sortOrder]);
    }

    /** @param array<int> $tagIds */
    public function syncTags(Report $report, array $tagIds): void
    {
        $report->tags()->sync($tagIds);
    }

    public function loadRelations(Report $report): Report
    {
        return $report->load(['photos', 'tags', 'reporter:id,name']);
    }

    public function findById(int $id): Report
    {
        return Report::query()->with(['photos', 'tags', 'reporter:id,name'])->findOrFail($id);
    }

    /** @return LengthAwarePaginator<int, Report> */
    public function paginateByReporter(int $reporterId, ?ReportStatus $status = null, int $perPage = 15): LengthAwarePaginator
    {
        return Report::query()->with(['photos', 'tags', 'reporter:id,name'])
            ->where('reporter_id', $reporterId)
            ->when($status, fn ($query) => $query->where('status', $status->value))
            ->latest()->orderByDesc('id')->paginate($perPage);
    }
}
