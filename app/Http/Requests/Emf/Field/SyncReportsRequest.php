<?php

namespace App\Http\Requests\Emf\Field;

use App\Http\Requests\Emf\EmfRequest;

class SyncReportsRequest extends EmfRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'reports' => ['required', 'array', 'min:1'],
            'reports.*.localId' => ['nullable', 'string', 'max:80'],
            'reports.*.payload' => ['nullable', 'array'],
        ];
    }
}
