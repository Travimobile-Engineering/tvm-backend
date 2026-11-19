<?php

namespace App\Http\Requests\Emf;

class LoginRequest extends EmfRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'serviceNumber' => ['required', 'string', 'max:32'],
            'password' => ['required', 'string', 'min:6'],
        ];
    }
}
