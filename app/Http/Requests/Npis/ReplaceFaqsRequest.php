<?php

namespace App\Http\Requests\Npis;

use Illuminate\Foundation\Http\FormRequest;

class ReplaceFaqsRequest extends FormRequest
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
            'items' => ['present', 'array', 'max:50'],
            'items.*.faqId' => ['nullable', 'string', 'max:64', 'exists:npis_faqs,id'],
            'items.*.question' => ['required', 'string', 'min:5', 'max:200'],
            'items.*.answer' => ['required', 'string', 'min:5', 'max:2000'],
            'items.*.category' => ['required', 'string', 'in:registration,travel,accommodation,programme,check_in,general'],
            'items.*.published' => ['nullable', 'boolean'],
        ];
    }
}
