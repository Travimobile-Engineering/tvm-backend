<?php

namespace App\Http\Requests\Emf;

class WatchlistImportRequest extends EmfRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'entries' => ['required', 'array', 'min:1'],
            'entries.*.type' => ['required', 'in:person,vehicle,phone,organization'],
            'entries.*.risk' => ['required', 'in:critical,high,medium,low'],
            'entries.*.reason' => ['required', 'string', 'max:500'],
            'entries.*.source' => ['required', 'string', 'max:160'],
        ];
    }
}
