<?php

namespace App\Http\Requests\Emf\Field;

use App\Http\Requests\Emf\EmfRequest;

class ReleaseRequest extends EmfRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'incidentId' => ['nullable', 'string', 'exists:emf_incidents,id'],
            'note' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
