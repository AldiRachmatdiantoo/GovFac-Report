<?php

namespace App\Modules\Verification\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApproveReportRequest extends FormRequest
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
            'note' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ];
    }

    public function note(): ?string
    {
        return $this->validated('note');
    }
}
