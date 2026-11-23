<?php

namespace App\Modules\Report\Contracts;

use Carbon\CarbonInterface;

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

    /**
     * Review events in an optional, inclusive time range, counted per event:
     * a report rejected, fixed and rejected again counts twice.
     *
     * - submitted: new reports (the first `ai_validation` entry)
     * - approved: admin approvals
     * - rejected: rejections by the admin or the AI
     * - rejected_by_reason: rejections per ReasonCategory value, every category present;
     *   a rejection without a category counts as `other`, so the parts always add up to `rejected`
     *
     * @return array{submitted: int, approved: int, rejected: int, rejected_by_reason: array<string, int>}
     */
    public function reviewSummary(?CarbonInterface $from = null, ?CarbonInterface $to = null): array;
}
