<?php

namespace App\Modules\Report\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Same body as creating a report, except that photos may be left out to keep the current ones.
 * When photos are sent they replace all existing photos.
 */
class UpdateReportRequest extends StoreReportRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'photos' => ['sometimes', 'array', 'min:1', 'max:5'],
        ];
    }
}
