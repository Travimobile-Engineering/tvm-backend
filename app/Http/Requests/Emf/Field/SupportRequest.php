<?php

namespace App\Http\Requests\Emf\Field;

use App\Http\Requests\Emf\EmfRequest;

class SupportRequest extends EmfRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', 'in:app_problem,correction,help'],
            'subject' => ['required', 'string', 'max:200'],
            'message' => ['required', 'string', 'max:4000'],
            'appVersion' => ['nullable', 'string', 'max:32'],
            'deviceInfo' => ['sometimes', 'array'],
        ];
    }
}
