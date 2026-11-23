<?php

namespace App\Http\Requests\Emf;

class AlertEscalateRequest extends EmfRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'severity' => ['required', 'in:critical,high,medium,low'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'assigneeId' => ['nullable', 'string', 'exists:emf_officers,id'],
        ];
    }
}
