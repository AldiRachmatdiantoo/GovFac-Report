<?php

namespace App\Modules\Report\Contracts;

use App\Modules\Report\Models\Report;

/**
 * Read access to reports for the admin review flow in the Verification module.
 * Status changes still go through ReportStatusService.
 */
interface ReportReview
{
    /**
     * @return Report|null with photos, tags and reporter loaded
     */
    public function findDetail(int $reportId): ?Report;
}
