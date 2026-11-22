<?php

namespace App\Http\Requests\Emf;

class NotificationPreferencesRequest extends EmfRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'preferences' => ['required', 'array'],
        ];
    }
}
