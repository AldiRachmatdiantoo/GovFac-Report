<?php

namespace App\Modules\Community\Http\Requests;

use App\Modules\Report\Contracts\ReportFeed;
use App\Modules\Report\Contracts\ReportFeedFilter;
use App\Modules\Shared\Enums\Criticality;
use App\Modules\Shared\Enums\ReportStatus;
use App\Modules\Shared\Support\Pagination;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FeedRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'q' => ['sometimes', 'nullable', 'string', 'max:100'],
            // Only statuses that can appear in the feed make sense as a filter.
            'status' => ['sometimes', 'string', Rule::enum(ReportStatus::class)->only(ReportFeed::VISIBLE_STATUSES)],
            'criticality' => ['sometimes', 'string', Rule::enum(Criticality::class)],
            'tag' => ['sometimes', 'nullable', 'string', 'max:31'],
            'sort' => ['sometimes', 'string', Rule::in(ReportFeedFilter::SORTS)],
            'per_page' => Pagination::rules(),
        ];
    }

    public function filter(): ReportFeedFilter
    {
        $search = trim((string) $this->validated('q', ''));
        $tag = mb_strtolower(ltrim(trim((string) $this->validated('tag', '')), '#'));

        return new ReportFeedFilter(
            search: $search === '' ? null : $search,
            status: $this->has('status') ? ReportStatus::from($this->validated('status')) : null,
            criticality: $this->has('criticality') ? Criticality::from($this->validated('criticality')) : null,
            tag: $tag === '' ? null : $tag,
            sort: $this->validated('sort', ReportFeedFilter::SORT_NEWEST),
            perPage: (int) $this->validated('per_page', Pagination::DEFAULT_PER_PAGE),
        );
    }
}
