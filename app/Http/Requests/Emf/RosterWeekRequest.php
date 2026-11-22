<?php

namespace App\Http\Requests\Emf;

class RosterWeekRequest extends EmfRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'weekStart' => ['required', 'date'],
        ];
    }
}
