<?php

namespace App\Http\Requests\Emf;

class IncidentConvertRequest extends EmfRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'caseTitle' => ['nullable', 'string', 'max:200'],
            'ownerId' => ['nullable', 'string', 'exists:emf_officers,id'],
            'dueDate' => ['nullable', 'date', 'after:today'],
        ];
    }
}
