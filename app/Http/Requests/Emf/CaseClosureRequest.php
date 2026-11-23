<?php

namespace App\Http\Requests\Emf;

class CaseClosureRequest extends EmfRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'note' => ['nullable', 'string', 'max:2000'],
            'shareWithDss' => ['sometimes', 'boolean'],
        ];
    }
}
