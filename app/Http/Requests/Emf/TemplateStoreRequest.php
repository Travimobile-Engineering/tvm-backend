<?php

namespace App\Http\Requests\Emf;

class TemplateStoreRequest extends EmfRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:500'],
            'scope' => ['required', 'string', 'max:160'],
            'modes' => ['required', 'array', 'min:1'],
            'modes.*' => ['in:road,sea,air,rail,hospitality'],
            'durationHours' => ['required', 'integer', 'min:1', 'max:720'],
            'accessCount' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }
}
