<?php

namespace App\Modules\Notification\Http\Requests;

use App\Modules\Shared\Support\Pagination;
use Illuminate\Foundation\Http\FormRequest;

class ListNotificationsRequest extends FormRequest
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
            'unread' => ['sometimes', 'boolean'],
            'per_page' => Pagination::rules(),
        ];
    }

    public function perPage(): int
    {
        return (int) $this->validated('per_page', Pagination::DEFAULT_PER_PAGE);
    }
}
