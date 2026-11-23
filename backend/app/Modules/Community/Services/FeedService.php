<?php

namespace App\Modules\Community\Services;

use App\Modules\Auth\Models\User;
use App\Modules\Community\Repositories\ReportLikeRepository;
use App\Modules\Report\Contracts\ReportFeed;
use App\Modules\Report\Contracts\ReportFeedFilter;
use App\Modules\Report\Models\Report;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class FeedService
{
    public function __construct(
        private readonly ReportFeed $feed,
        private readonly ReportLikeRepository $likes,
    ) {}

    /**
     * Each report carries a `liked_by_me` attribute for the viewer.
     *
     * @return LengthAwarePaginator<int, Report>
     */
    public function paginate(User $viewer, ReportFeedFilter $filter): LengthAwarePaginator
    {
        $page = $this->feed->paginate($filter);

        $reportIds = array_map(fn (Report $report): int => $report->getKey(), $page->items());
        $liked = array_flip($this->likes->likedReportIds($viewer->getKey(), $reportIds));

        foreach ($page->items() as $report) {
            $report->setAttribute('liked_by_me', isset($liked[$report->getKey()]));
        }

        return $page;
    }
}
