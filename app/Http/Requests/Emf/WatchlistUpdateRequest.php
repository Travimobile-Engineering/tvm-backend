<?php

namespace App\Http\Requests\Emf;

class WatchlistUpdateRequest extends EmfRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'verificationCode' => ['required', 'digits:6'],
            'type' => ['sometimes', 'in:person,vehicle,phone,organization'],
            'risk' => ['sometimes', 'in:critical,high,medium,low'],
            'reason' => ['sometimes', 'string', 'max:500'],
            'source' => ['sometimes', 'string', 'max:160'],
            'fullName' => ['sometimes', 'nullable', 'string', 'max:160'],
            'nin' => ['sometimes', 'nullable', 'digits:11'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:32'],
            'plateNumber' => ['sometimes', 'nullable', 'string', 'max:24'],
            'dob' => ['sometimes', 'nullable', 'date', 'before:today'],
            'nationality' => ['sometimes', 'nullable', 'string', 'max:64'],
            'address' => ['sometimes', 'nullable', 'string', 'max:500'],
            'expiresAt' => ['sometimes', 'nullable', 'date', 'after:now'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ];
    }
}
