<?php

namespace App\Modules\Analytics\Services;

use App\Modules\Report\Contracts\ReportStatistics;
use Carbon\CarbonInterface;

class AdminReportStatsService
{
    public function __construct(private readonly ReportStatistics $reports) {}

    /**
     * @return array{from: string|null, to: string|null, submitted: int, approved: int, rejected: int, rejected_by_reason: array<string, int>}
     */
    public function summary(?CarbonInterface $from, ?CarbonInterface $to): array
    {
        return [
            'from' => $from?->toDateString(),
            'to' => $to?->toDateString(),
            ...$this->reports->reviewSummary($from, $to),
        ];
    }
}
