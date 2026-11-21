<?php

namespace App\Http\Requests\Emf;

class IncidentAssignRequest extends EmfRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'officerId' => ['required', 'string', 'exists:emf_officers,id'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
