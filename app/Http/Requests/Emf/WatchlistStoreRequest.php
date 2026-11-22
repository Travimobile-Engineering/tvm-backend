<?php

namespace App\Http\Requests\Emf;

class WatchlistStoreRequest extends EmfRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', 'in:person,vehicle,phone,organization'],
            'risk' => ['required', 'in:critical,high,medium,low'],
            'reason' => ['required', 'string', 'max:500'],
            'source' => ['required', 'string', 'max:160'],
            'fullName' => ['nullable', 'string', 'max:160'],
            'nin' => ['nullable', 'digits:11'],
            'phone' => ['nullable', 'string', 'max:32'],
            'plateNumber' => ['nullable', 'string', 'max:24'],
            'dob' => ['nullable', 'date', 'before:today'],
            'nationality' => ['nullable', 'string', 'max:64'],
            'address' => ['nullable', 'string', 'max:500'],
            'expiresAt' => ['nullable', 'date', 'after:now'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
