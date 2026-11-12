<?php

namespace App\Modules\Report\Http\Requests;

use App\Modules\Shared\Enums\ReportStatus;
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
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ];
    }

    public function status(): ?ReportStatus
    {
        return $this->has('status') ? ReportStatus::from($this->validated('status')) : null;
    }

    public function perPage(): int
    {
        return (int) $this->validated('per_page', 15);
    }
}
