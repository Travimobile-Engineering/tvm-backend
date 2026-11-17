<?php

namespace App\Http\Requests\Npis;

use App\Services\Npis\EventService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class TrainingSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:5', 'max:200'],
            'track' => ['required', 'string', 'in:transportation,hospitality'],
            'subsector' => ['nullable', 'string', 'in:road,air,sea,rail'],
            'date' => ['required', 'date_format:Y-m-d'],
            'startTime' => ['required', 'date_format:H:i'],
            'endTime' => ['required', 'date_format:H:i', 'after:startTime'],
            'room' => ['required', 'string', 'max:120'],
            'capacity' => ['required', 'integer', 'min:1', 'max:1000'],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function ($validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $event = app(EventService::class)->current();

                if (! $event->isWithinEventDates((string) $this->input('date'))) {
                    $validator->errors()->add('date', 'Sessions must fall within the summit dates.');
                }

                if ($this->input('track') === 'hospitality' && $this->filled('subsector')) {
                    $validator->errors()->add('subsector', 'Hospitality sessions do not use a transport subsector.');
                }

                if ($this->input('track') === 'transportation' && ! $this->filled('subsector')) {
                    $validator->errors()->add('subsector', 'Choose a transport subsector.');
                }
            },
        ];
    }
}
