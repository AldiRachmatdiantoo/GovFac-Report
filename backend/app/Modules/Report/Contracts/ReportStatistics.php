<?php

namespace App\Modules\Report\Contracts;

/**
 * Read-only report counts for modules that own no report data, such as Analytics.
 */
interface ReportStatistics
{
    /**
     * Report count per status for one reporter. Every status is present, zero included.
     *
     * @return array<string, int> keyed by ReportStatus value
     */
    public function countByStatusForReporter(int $reporterId): array;
}
