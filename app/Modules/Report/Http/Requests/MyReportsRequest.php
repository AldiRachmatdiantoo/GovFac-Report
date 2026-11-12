<?php

namespace App\Modules\Report\Http\Requests;

use App\Modules\Shared\Enums\ReportStatus;
use App\Modules\Shared\Support\Pagination;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MyReportsRequest extends FormRequest
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
            'status' => ['sometimes', 'string', Rule::enum(ReportStatus::class)],
            'per_page' => Pagination::rules(),
        ];
    }

    public function status(): ?ReportStatus
    {
        return $this->has('status') ? ReportStatus::from($this->validated('status')) : null;
    }

    public function perPage(): int
    {
        return (int) $this->validated('per_page', Pagination::DEFAULT_PER_PAGE);
    }
}
