<?php

namespace App\Http\Requests\Emf;

class ActivateOfficerRequest extends EmfRequest
{
    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'serviceNumber' => 'required|string|max:32',
            'token' => 'required|string|max:128',
            'password' => 'required|string|min:8|confirmed',
        ];
    }
}
