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
            ->when($filter->search !== null, fn (Builder $query) => $this->search($query, $filter->search))
            ->when($filter->status !== null, fn (Builder $query) => $query->where('status', $filter->status))
            ->when($filter->criticality !== null, fn (Builder $query) => $query->where('criticality', $filter->criticality))
            ->when($filter->tag !== null, fn (Builder $query) => $query->whereHas('tags', fn (Builder $tags) => $tags->where('name', $filter->tag)))
            ->when($filter->sort === ReportFeedFilter::SORT_LIKES, fn (Builder $query) => $query->orderByDesc('likes_count'))
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

    /**
     * @param  Builder<Report>  $query
     */
    private function search(Builder $query, string $term): void
    {
        // lower() on both sides keeps the match case-insensitive on PostgreSQL and SQLite alike.
        $pattern = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], mb_strtolower($term)).'%';

        $query->where(function (Builder $any) use ($pattern): void {
            foreach (['title', 'description', 'code'] as $column) {
                $any->orWhereRaw("lower({$column}) LIKE ? ESCAPE '!'", [$pattern]);
            }
        });
    }
}
