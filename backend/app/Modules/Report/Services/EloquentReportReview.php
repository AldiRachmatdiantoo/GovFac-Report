<?php

namespace App\Modules\Report\Services;

use App\Modules\Report\Contracts\ReportReview;
use App\Modules\Report\Models\Report;
use App\Modules\Report\Repositories\ReportReviewRepository;

class EloquentReportReview implements ReportReview
{
    public function __construct(private readonly ReportReviewRepository $reports) {}

    public function findDetail(int $reportId): ?Report
    {
        return $this->reports->findDetail($reportId);
    }
}
