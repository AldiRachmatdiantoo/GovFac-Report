<?php

namespace App\Modules\Report\Repositories;

use App\Modules\Report\Models\Report;
use App\Modules\Report\Models\ReportStatusHistory;
use App\Modules\Shared\Enums\ReportStatus;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

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

    public function countSubmitted(?CarbonInterface $from, ?CarbonInterface $to): int
    {
        return $this->histories($from, $to)
            ->whereNull('from_status')
            ->where('to_status', ReportStatus::AiValidation)
            ->count();
    }

    public function countApprovedByAdmin(?CarbonInterface $from, ?CarbonInterface $to): int
    {
        return $this->histories($from, $to)
            ->where('from_status', ReportStatus::AdminReview)
            ->where('to_status', ReportStatus::Approved)
            ->count();
    }

    /**
     * @return array<string, int> keyed by reason category, '' for rejections without one
     */
    public function countRejectedByReason(?CarbonInterface $from, ?CarbonInterface $to): array
    {
        return $this->histories($from, $to)
            ->where('to_status', ReportStatus::Rejected)
            ->groupBy('reason_category')
            ->selectRaw('reason_category, count(*) as aggregate')
            ->toBase()
            ->get()
            ->mapWithKeys(fn (object $row): array => [(string) $row->reason_category => (int) $row->aggregate])
            ->all();
    }

    /**
     * @return Builder<ReportStatusHistory>
     */
    private function histories(?CarbonInterface $from, ?CarbonInterface $to): Builder
    {
        return ReportStatusHistory::query()
            ->when($from !== null, fn (Builder $query) => $query->where('created_at', '>=', $from))
            ->when($to !== null, fn (Builder $query) => $query->where('created_at', '<=', $to));
    }
}
