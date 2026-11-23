<?php

namespace App\Http\Requests\Emf;

class AnalyticsExportRequest extends EmfRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'digits:6'],
            'watermark' => ['sometimes', 'boolean'],
            'period' => ['sometimes', 'in:24h,7d,30d,quarter'],
            'stateCode' => ['sometimes', 'string', 'max:8'],
            'mode' => ['sometimes', 'in:road,sea,air,rail,hospitality'],
            'verdict' => ['sometimes', 'in:clear,flagged,mismatch,unverified'],
        ];
    }
}
