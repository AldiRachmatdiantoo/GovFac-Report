<?php

namespace App\Modules\Community\Repositories;

use App\Modules\Community\Models\ReportLike;

class ReportLikeRepository
{
    /**
     * @return bool true when a new like was stored, false when the user had already liked the report
     */
    public function add(int $reportId, int $userId): bool
    {
        return ReportLike::query()->insertOrIgnore([
            'report_id' => $reportId,
            'user_id' => $userId,
            'created_at' => now(),
        ]) === 1;
    }

    /**
     * @return bool true when a like was removed
     */
    public function remove(int $reportId, int $userId): bool
    {
        return ReportLike::query()
            ->where('report_id', $reportId)
            ->where('user_id', $userId)
            ->delete() === 1;
    }

    /**
     * @param  list<int>  $reportIds
     * @return list<int> the subset of $reportIds the user has liked
     */
    public function likedReportIds(int $userId, array $reportIds): array
    {
        if ($reportIds === []) {
            return [];
        }

        return ReportLike::query()
            ->where('user_id', $userId)
            ->whereIn('report_id', $reportIds)
            ->pluck('report_id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }
}
