<?php

namespace App\Modules\Report\Services;

use App\Modules\Report\Contracts\ReportFeed;
use App\Modules\Report\Contracts\ReportFeedFilter;
use App\Modules\Report\Models\Report;
use App\Modules\Report\Repositories\ReportFeedRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EloquentReportFeed implements ReportFeed
{
    public function __construct(private readonly ReportFeedRepository $feed) {}

    public function paginate(ReportFeedFilter $filter): LengthAwarePaginator
    {
        return $this->feed->paginate($filter);
    }

    public function findVisible(int $reportId): ?Report
    {
        return $this->feed->findVisible($reportId);
    }
}
