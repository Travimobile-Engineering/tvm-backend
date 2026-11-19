<?php

namespace App\Http\Requests\Emf;

class ScheduleStoreRequest extends EmfRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:160'],
            'reportType' => ['required', 'string', 'max:40'],
            'frequency' => ['required', 'in:daily,weekly,monthly'],
            'recipients' => ['required', 'array', 'min:1'],
            'recipients.*' => ['email'],
            'filters' => ['sometimes', 'array'],
            'nextRunAt' => ['nullable', 'date', 'after:now'],
        ];
    }
}
