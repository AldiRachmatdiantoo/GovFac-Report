<?php

namespace App\Modules\Analytics\Http\Requests;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;

class AdminStatsRequest extends FormRequest
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
            'from' => ['sometimes', 'date_format:Y-m-d'],
            'to' => ['sometimes', 'date_format:Y-m-d', 'after_or_equal:from'],
        ];
    }

    /**
     * Start of the `from` day in UTC.
     */
    public function from(): ?CarbonImmutable
    {
        return $this->has('from') ? CarbonImmutable::createFromFormat('Y-m-d', $this->validated('from'), 'UTC')->startOfDay() : null;
    }

    /**
     * End of the `to` day in UTC, so the whole day is included.
     */
    public function to(): ?CarbonImmutable
    {
        return $this->has('to') ? CarbonImmutable::createFromFormat('Y-m-d', $this->validated('to'), 'UTC')->endOfDay() : null;
    }
}
