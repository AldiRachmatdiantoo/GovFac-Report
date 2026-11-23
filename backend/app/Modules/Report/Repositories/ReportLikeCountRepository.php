<?php

namespace App\Modules\Report\Repositories;

use App\Modules\Report\Models\Report;

/**
 * Goes through the base query builder so a like never touches `updated_at`:
 * a like is not a change to the report itself.
 */
class ReportLikeCountRepository
{
    public function increment(int $reportId): void
    {
        Report::query()->whereKey($reportId)->toBase()->increment('likes_count');
    }

    public function decrement(int $reportId): void
    {
        Report::query()->whereKey($reportId)->where('likes_count', '>', 0)->toBase()->decrement('likes_count');
    }

    public function count(int $reportId): int
    {
        return (int) Report::query()->whereKey($reportId)->value('likes_count');
    }
}
