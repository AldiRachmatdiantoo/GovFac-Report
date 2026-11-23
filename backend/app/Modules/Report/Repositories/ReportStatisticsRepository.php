<?php

namespace App\Modules\Report\Repositories;

use App\Modules\Report\Models\Report;

class ReportStatisticsRepository
{
    /**
     * @return array<string, int> only the statuses that have reports
     */
    public function countByStatusForReporter(int $reporterId): array
    {
        return Report::query()
            ->where('reporter_id', $reporterId)
            ->groupBy('status')
            ->selectRaw('status, count(*) as aggregate')
            ->toBase()
            ->pluck('aggregate', 'status')
            ->map(fn ($count): int => (int) $count)
            ->all();
    }
}
