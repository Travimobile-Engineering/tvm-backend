<?php

namespace App\Http\Requests\Emf\Field;

use App\Http\Requests\Emf\EmfRequest;

class FlagSeatRequest extends EmfRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'reason' => ['required', 'in:photo_mismatch,name_mismatch,watchlist_hit,unverified_id,suspected_impersonation,other'],
            'note' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
