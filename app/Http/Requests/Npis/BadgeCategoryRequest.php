<?php

namespace App\Http\Requests\Npis;

use Illuminate\Foundation\Http\FormRequest;

class BadgeCategoryRequest extends FormRequest
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
            'category' => ['present', 'nullable', 'string', 'exists:npis_protocol_categories,code'],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }
}
