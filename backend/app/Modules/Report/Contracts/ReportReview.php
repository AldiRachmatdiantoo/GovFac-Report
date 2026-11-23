<?php

namespace App\Modules\Report\Contracts;

use App\Modules\Report\Models\Report;
use App\Modules\Report\Models\ReportStatusHistory;
use App\Modules\Shared\Support\Pagination;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

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

    /**
     * Reports in `admin_review`: most liked first, then the one waiting longest.
     * Each report carries a `waiting_since` attribute: when it last entered `admin_review`.
     *
     * @return LengthAwarePaginator<int, Report> with photos, tags and reporter loaded
     */
    public function paginateAwaitingReview(int $perPage = Pagination::DEFAULT_PER_PAGE): LengthAwarePaginator;

    /**
     * Admin decisions (admin_review -> approved or rejected), newest first.
     * A report that was rejected, fixed and decided again appears once per decision.
     *
     * @return LengthAwarePaginator<int, ReportStatusHistory> with actor and report.reporter loaded
     */
    public function paginateDecisions(ReviewDecisionFilter $filter): LengthAwarePaginator;
}
