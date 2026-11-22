<?php

namespace App\Http\Requests\Emf;

class CaseStoreRequest extends EmfRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'severity' => ['required', 'in:critical,high,medium,low'],
            'ownerId' => ['nullable', 'string', 'exists:emf_officers,id'],
            'dueDate' => ['nullable', 'date', 'after:today'],
            'summary' => ['nullable', 'string', 'max:4000'],
            'linkedRecords' => ['sometimes', 'array'],
        ];
    }
}
