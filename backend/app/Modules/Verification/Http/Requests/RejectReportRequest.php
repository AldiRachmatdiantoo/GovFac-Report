<?php

namespace App\Modules\Verification\Http\Requests;

use App\Modules\Shared\Enums\ReasonCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RejectReportRequest extends FormRequest
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
            'reason_category' => ['required', 'string', Rule::enum(ReasonCategory::class)],
            'note' => ['required', 'string', 'min:10', 'max:1000'],
        ];
    }

    public function reasonCategory(): ReasonCategory
    {
        return ReasonCategory::from($this->validated('reason_category'));
    }

    public function note(): string
    {
        return $this->validated('note');
    }
}
