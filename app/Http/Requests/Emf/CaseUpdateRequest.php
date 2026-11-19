<?php

namespace App\Http\Requests\Emf;

class CaseUpdateRequest extends EmfRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:200'],
            'severity' => ['sometimes', 'in:critical,high,medium,low'],
            'ownerId' => ['sometimes', 'string', 'exists:emf_officers,id'],
            'dueDate' => ['sometimes', 'nullable', 'date'],
            'summary' => ['sometimes', 'nullable', 'string', 'max:4000'],
        ];
    }
}
