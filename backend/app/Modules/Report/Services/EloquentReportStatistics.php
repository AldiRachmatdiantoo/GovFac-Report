<?php

namespace App\Modules\Report\Services;

use App\Modules\Report\Contracts\ReportStatistics;
use App\Modules\Report\Repositories\ReportStatisticsRepository;
use App\Modules\Shared\Enums\ReportStatus;

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
}
