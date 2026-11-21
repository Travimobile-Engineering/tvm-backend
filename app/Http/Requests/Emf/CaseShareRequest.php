<?php

namespace App\Http\Requests\Emf;

class CaseShareRequest extends EmfRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'agency' => ['required', 'string', 'max:160'],
            'expiresAt' => ['nullable', 'date', 'after:now'],
            'code' => ['required', 'digits:6'],
        ];
    }
}
