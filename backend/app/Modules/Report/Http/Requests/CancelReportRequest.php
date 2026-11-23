<?php

namespace App\Modules\Report\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CancelReportRequest extends FormRequest
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
            'reason' => ['sometimes', 'nullable', 'string', 'max:500'],
        ];
    }

    public function reason(): ?string
    {
        return $this->validated('reason');
    }
}
