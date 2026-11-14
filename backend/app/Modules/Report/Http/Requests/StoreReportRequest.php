<?php

namespace App\Modules\Report\Http\Requests;

use App\Modules\Shared\Enums\UserRole;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreReportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasRole(UserRole::User) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:100'],
            'description' => ['required', 'string', 'max:2000'],
            'photos' => ['required', 'array', 'min:1', 'max:5'],
            'photos.*' => ['required', 'image', 'mimes:jpeg,png', 'max:5120'],
            'tags' => ['required', 'array', 'min:1', 'max:5'],
            'tags.*' => ['required', 'string', 'max:30', 'regex:/\A[\pL\pN_]+\z/u'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (is_array($this->input('tags'))) {
            $this->merge(['tags' => array_map(
                fn ($tag) => is_string($tag) ? mb_strtolower(ltrim($tag, '#')) : $tag,
                $this->input('tags'),
            )]);
        }
    }
}
