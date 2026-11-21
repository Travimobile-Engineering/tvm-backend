<?php

namespace App\Http\Requests\Emf\Field;

use App\Http\Requests\Emf\EmfRequest;

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
