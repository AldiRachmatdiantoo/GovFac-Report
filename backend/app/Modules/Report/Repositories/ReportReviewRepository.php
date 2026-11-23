<?php

namespace App\Modules\Report\Repositories;

use App\Modules\Report\Contracts\ReviewDecisionFilter;
use App\Modules\Report\Models\Report;
use App\Modules\Report\Models\ReportStatusHistory;
use App\Modules\Shared\Enums\ReportStatus;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class ReportReviewRepository
{
    public function findDetail(int $reportId): ?Report
    {
        return Report::query()
            ->with(['photos', 'tags', 'reporter:id,name'])
            ->find($reportId);
    }

    /**
     * @return LengthAwarePaginator<int, Report>
     */
    public function paginateAwaitingReview(int $perPage): LengthAwarePaginator
    {
        $waitingSince = ReportStatusHistory::query()
            ->selectRaw('max(created_at)')
            ->whereColumn('report_id', 'reports.id')
            ->where('to_status', ReportStatus::AdminReview);

        return Report::query()
            ->select('reports.*')
            ->selectSub($waitingSince, 'waiting_since')
            ->with(['photos', 'tags', 'reporter:id,name'])
            ->where('status', ReportStatus::AdminReview)
            ->orderByDesc('likes_count')
            ->orderBy('waiting_since')
            ->orderBy('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @return LengthAwarePaginator<int, ReportStatusHistory>
     */
    public function paginateDecisions(ReviewDecisionFilter $filter): LengthAwarePaginator
    {
        $decisions = $filter->decision !== null ? [$filter->decision] : [ReportStatus::Approved, ReportStatus::Rejected];

        return ReportStatusHistory::query()
            ->with(['actor:id,name', 'report.reporter:id,name'])
            ->where('from_status', ReportStatus::AdminReview)
            ->whereIn('to_status', $decisions)
            ->when($filter->from !== null, fn (Builder $query) => $query->where('created_at', '>=', $filter->from))
            ->when($filter->to !== null, fn (Builder $query) => $query->where('created_at', '<=', $filter->to))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($filter->perPage)
            ->withQueryString();
    }
}
