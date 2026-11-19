<?php

namespace App\Http\Requests\Emf;

class WatchlistRemoveRequest extends EmfRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'verificationCode' => ['required', 'digits:6'],
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }
}
