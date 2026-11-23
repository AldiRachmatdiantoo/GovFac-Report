<?php

namespace App\Modules\Report\Services;

use App\Modules\Report\Contracts\ReportReview;
use App\Modules\Report\Contracts\ReviewDecisionFilter;
use App\Modules\Report\Models\Report;
use App\Modules\Report\Repositories\ReportReviewRepository;
use App\Modules\Shared\Support\Pagination;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EloquentReportReview implements ReportReview
{
    public function __construct(private readonly ReportReviewRepository $reports) {}

    public function findDetail(int $reportId): ?Report
    {
        return $this->reports->findDetail($reportId);
    }

    public function paginateAwaitingReview(int $perPage = Pagination::DEFAULT_PER_PAGE): LengthAwarePaginator
    {
        return $this->reports->paginateAwaitingReview($perPage);
    }

    public function paginateDecisions(ReviewDecisionFilter $filter): LengthAwarePaginator
    {
        return $this->reports->paginateDecisions($filter);
    }
}
