<?php

namespace App\Modules\Report\Services;

use App\Modules\Report\Contracts\ReportStatistics;
use App\Modules\Report\Repositories\ReportStatisticsRepository;
use App\Modules\Shared\Enums\ReasonCategory;
use App\Modules\Shared\Enums\ReportStatus;
use Carbon\CarbonInterface;

class EloquentReportStatistics implements ReportStatistics
{
    public function __construct(private readonly ReportStatisticsRepository $statistics) {}

    public function countByStatusForReporter(int $reporterId): array
    {
        $counts = $this->statistics->countByStatusForReporter($reporterId);

        $byStatus = [];

        foreach (ReportStatus::cases() as $status) {
            $byStatus[$status->value] = $counts[$status->value] ?? 0;
        }

        return $byStatus;
    }

    public function reviewSummary(?CarbonInterface $from = null, ?CarbonInterface $to = null): array
    {
        $byReason = array_fill_keys(array_column(ReasonCategory::cases(), 'value'), 0);

        foreach ($this->statistics->countRejectedByReason($from, $to) as $category => $count) {
            $key = ReasonCategory::tryFrom($category)?->value ?? ReasonCategory::Other->value;
            $byReason[$key] += $count;
        }

        return [
            'submitted' => $this->statistics->countSubmitted($from, $to),
            'approved' => $this->statistics->countApprovedByAdmin($from, $to),
            'rejected' => array_sum($byReason),
            'rejected_by_reason' => $byReason,
        ];
    }
}
