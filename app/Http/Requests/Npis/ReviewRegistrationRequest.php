<?php

namespace App\Http\Requests\Npis;

use Illuminate\Foundation\Http\FormRequest;

class ReviewRegistrationRequest extends FormRequest
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
            'action' => ['required', 'string', 'in:approve,reject,mark_not_duplicate,merge_duplicate,accept_unlisted_unit'],
            'note' => ['nullable', 'string', 'max:1000', 'required_if:action,reject'],
            'mergeIntoRegistrationId' => ['nullable', 'string', 'max:64', 'required_if:action,merge_duplicate'],
            'unitId' => ['nullable', 'string', 'max:64'],
        ];
    }
}
