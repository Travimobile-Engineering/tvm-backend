<?php

namespace App\Http\Requests\Emf;

class ProfileUpdateRequest extends EmfRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'fullName' => ['sometimes', 'string', 'max:160'],
            'phone' => ['sometimes', 'string', 'max:32'],
            'language' => ['sometimes', 'in:en,ha,ig,yo'],
        ];
    }
}
