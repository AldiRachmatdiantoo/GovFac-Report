<?php

namespace App\Modules\Report\Contracts;

use App\Modules\Report\Models\Report;
use App\Modules\Shared\Enums\ReportStatus;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Read access to the community feed for the Community module.
 *
 * The feed holds every report that passed AI validation and is still alive:
 * `rejected` and `cancelled` reports never appear, nor do reports still waiting for the AI.
 */
interface ReportFeed
{
    public const VISIBLE_STATUSES = [
        ReportStatus::AdminReview,
        ReportStatus::Approved,
        ReportStatus::Forwarded,
        ReportStatus::GovApproved,
        ReportStatus::Scheduled,
    ];

    /**
     * @return LengthAwarePaginator<int, Report> with photos, tags and reporter loaded
     */
    public function paginate(ReportFeedFilter $filter): LengthAwarePaginator;

    /**
     * The report when it is in the feed; null when it is missing or not visible there.
     */
    public function findVisible(int $reportId): ?Report;
}
