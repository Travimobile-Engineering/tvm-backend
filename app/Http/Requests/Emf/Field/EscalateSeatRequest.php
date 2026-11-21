<?php

namespace App\Http\Requests\Emf\Field;

use App\Http\Requests\Emf\EmfRequest;

class EscalateSeatRequest extends EmfRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'category' => ['required', 'in:watchlist_hit,suspected_impersonation,trafficking,refusal,kidnapping,contraband'],
            'severity' => ['sometimes', 'in:critical,high,medium,low'],
            'actionTaken' => ['required', 'in:vehicle_detained,subject_isolated,vehicle_fled'],
            'note' => ['nullable', 'string', 'max:2000'],
            'assigneeId' => ['nullable', 'string', 'exists:emf_officers,id'],
        ];
    }
}
