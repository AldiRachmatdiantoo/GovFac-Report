<?php

namespace App\Modules\Verification\Http\Requests;

use App\Modules\Report\Contracts\ReviewDecisionFilter;
use App\Modules\Shared\Enums\ReportStatus;
use App\Modules\Shared\Support\Pagination;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminHistoryRequest extends FormRequest
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
            'decision' => ['sometimes', 'string', Rule::in([ReportStatus::Approved->value, ReportStatus::Rejected->value])],
            'from' => ['sometimes', 'date_format:Y-m-d'],
            'to' => ['sometimes', 'date_format:Y-m-d', 'after_or_equal:from'],
            'per_page' => Pagination::rules(),
        ];
    }

    /**
     * `from` and `to` are whole days in UTC, both inclusive.
     */
    public function filter(): ReviewDecisionFilter
    {
        return new ReviewDecisionFilter(
            decision: $this->has('decision') ? ReportStatus::from($this->validated('decision')) : null,
            from: $this->has('from') ? CarbonImmutable::createFromFormat('Y-m-d', $this->validated('from'), 'UTC')->startOfDay() : null,
            to: $this->has('to') ? CarbonImmutable::createFromFormat('Y-m-d', $this->validated('to'), 'UTC')->endOfDay() : null,
            perPage: (int) $this->validated('per_page', Pagination::DEFAULT_PER_PAGE),
        );
    }
}
