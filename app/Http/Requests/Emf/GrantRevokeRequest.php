<?php

namespace App\Http\Requests\Emf;

class GrantRevokeRequest extends EmfRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'digits:6'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
