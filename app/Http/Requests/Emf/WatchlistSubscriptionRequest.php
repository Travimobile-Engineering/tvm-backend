<?php

namespace App\Http\Requests\Emf;

class WatchlistSubscriptionRequest extends EmfRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'subscriberName' => ['required', 'string', 'max:160'],
            'agency' => ['required', 'string', 'max:160'],
            'scope' => ['required', 'string', 'max:160'],
            'modes' => ['required', 'array', 'min:1'],
            'modes.*' => ['in:road,sea,air,rail,hospitality'],
            'notifyEmail' => ['sometimes', 'boolean'],
        ];
    }
}
