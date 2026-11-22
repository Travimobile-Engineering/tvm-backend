<?php

namespace App\Http\Requests\Emf;

class RosterAssignRequest extends EmfRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'dutyPointId' => ['required', 'string', 'exists:emf_duty_points,id'],
            'officerId' => ['required', 'string', 'exists:emf_officers,id'],
            'date' => ['required', 'date'],
            'shift' => ['required', 'in:morning,afternoon,night,day'],
        ];
    }
}
