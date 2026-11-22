<?php

namespace App\Http\Requests\Emf;

class GrantConflictRequest extends EmfRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'officerIds' => ['required', 'array', 'min:1'],
            'officerIds.*' => ['string', 'exists:emf_officers,id'],
            'startsAt' => ['required', 'date'],
            'endsAt' => ['required', 'date', 'after:startsAt'],
            'modes' => ['required', 'array', 'min:1'],
            'modes.*' => ['in:road,sea,air,rail,hospitality'],
        ];
    }
}
