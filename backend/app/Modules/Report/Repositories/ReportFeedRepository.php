<?php

namespace App\Modules\Report\Repositories;

use App\Modules\Report\Contracts\ReportFeed;
use App\Modules\Report\Contracts\ReportFeedFilter;
use App\Modules\Report\Models\Report;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class ReportFeedRepository
{
    /**
     * @return LengthAwarePaginator<int, Report>
     */
    public function paginate(ReportFeedFilter $filter): LengthAwarePaginator
    {
        return $this->visible()
            ->with(['photos', 'tags', 'reporter:id,name'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($filter->perPage)
            ->withQueryString();
    }

    public function findVisible(int $reportId): ?Report
    {
        return $this->visible()->find($reportId);
    }

    /**
     * @return Builder<Report>
     */
    private function visible(): Builder
    {
        return Report::query()->whereIn('status', ReportFeed::VISIBLE_STATUSES);
    }
}
