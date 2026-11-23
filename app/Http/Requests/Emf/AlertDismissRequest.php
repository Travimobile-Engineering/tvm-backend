<?php

namespace App\Http\Requests\Emf;

class AlertDismissRequest extends EmfRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'reason' => ['required', 'in:false_positive,duplicate,insufficient_evidence,data_entry_error'],
            'note' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
