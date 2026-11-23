<?php

namespace App\Modules\Community\Http\Requests;

use App\Modules\Report\Contracts\ReportFeedFilter;
use App\Modules\Shared\Support\Pagination;
use Illuminate\Foundation\Http\FormRequest;

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
            'per_page' => Pagination::rules(),
        ];
    }

    public function filter(): ReportFeedFilter
    {
        return new ReportFeedFilter(
            perPage: (int) $this->validated('per_page', Pagination::DEFAULT_PER_PAGE),
        );
    }
}
