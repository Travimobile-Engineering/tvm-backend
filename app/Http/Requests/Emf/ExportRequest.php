<?php

namespace App\Http\Requests\Emf;

class ExportRequest extends EmfRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'reportType' => ['required', 'in:manifests,watchlist,cases,audit,analytics'],
            'filters' => ['sometimes', 'array'],
            'code' => ['nullable', 'digits:6'],
            'watermark' => ['sometimes', 'boolean'],
            'requires2fa' => ['sometimes', 'boolean'],
        ];
    }
}
