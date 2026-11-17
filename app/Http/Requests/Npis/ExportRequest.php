<?php

namespace App\Http\Requests\Npis;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExportRequest extends FormRequest
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
        $keys = array_column(config('npis.exports.fields', []), 'key');

        return [
            'filters' => ['nullable', 'array'],
            'fields' => ['nullable', 'array', 'min:1'],
            'fields.*' => ['string', Rule::in($keys)],
            'format' => ['nullable', 'string', 'in:csv'],
        ];
    }
}
