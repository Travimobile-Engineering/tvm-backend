<?php

namespace App\Http\Requests\Npis;

use App\Services\Npis\EventService;
use App\Support\Npis\ItineraryValidator;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class UpdateItineraryRequest extends FormRequest
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
            'travelMode' => ['required', 'string', 'in:air,road,rail,sea,undecided'],
            'arrival' => ['required', 'array'],
            'arrival.planStatus' => ['required', 'string', 'in:undecided,preferred,confirmed'],
            'arrival.date' => ['nullable', 'date_format:Y-m-d'],
            'arrival.time' => ['nullable', 'date_format:H:i'],
            'arrival.timeWindow' => ['nullable', 'string', 'in:early_morning,morning,afternoon,evening,night'],
            'departure' => ['required', 'array'],
            'departure.planStatus' => ['required', 'string', 'in:undecided,preferred,confirmed'],
            'departure.date' => ['nullable', 'date_format:Y-m-d'],
            'departure.time' => ['nullable', 'date_format:H:i'],
            'departure.timeWindow' => ['nullable', 'string', 'in:early_morning,morning,afternoon,evening,night'],
            'postSummitPlan' => ['required', 'string', 'in:leave_immediately,extend_stay,undecided'],
            'flight' => ['nullable', 'array'],
            'flight.departureAirportCode' => ['nullable', 'string', 'max:8'],
            'flight.flightTime' => ['nullable', 'date_format:H:i'],
            'flight.flightTimeWindow' => ['nullable', 'string', 'in:early_morning,morning,afternoon,evening,night'],
            'flight.flightNumber' => ['nullable', 'string', 'max:16'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $event = app(EventService::class)->current();
                $errors = app(ItineraryValidator::class)->validate($this->all(), $event);

                if ($errors !== []) {
                    throw ValidationException::withMessages($errors);
                }
            },
        ];
    }
}
