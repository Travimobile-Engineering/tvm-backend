<?php

namespace App\Http\Requests\Emf;

class GrantStoreRequest extends EmfRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'officerId' => ['required', 'string', 'exists:emf_officers,id'],
            'templateId' => ['nullable', 'string', 'exists:emf_grant_templates,id'],
            'scope' => ['nullable', 'string', 'max:160'],
            'modes' => ['sometimes', 'array', 'min:1'],
            'modes.*' => ['in:road,sea,air,rail,hospitality'],
            'startsAt' => ['nullable', 'date'],
            'endsAt' => ['nullable', 'date', 'after:startsAt'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'overrideConflict' => ['sometimes', 'boolean'],
        ];
    }
}
