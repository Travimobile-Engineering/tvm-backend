<?php

namespace App\Http\Requests\Emf;

class AlertAssignRequest extends EmfRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'officerId' => ['required', 'string', 'exists:emf_officers,id'],
            'actionType' => ['required', 'in:2fa,sms,push,radio'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
