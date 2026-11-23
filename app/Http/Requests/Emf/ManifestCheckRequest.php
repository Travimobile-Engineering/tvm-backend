<?php

namespace App\Http\Requests\Emf;

class ManifestCheckRequest extends EmfRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'manifestNumber' => ['nullable', 'string', 'max:40'],
            'recheck' => ['sometimes', 'boolean'],
            'mode' => ['required', 'in:road,sea,air,rail,hospitality'],
            'operator' => ['required', 'string', 'max:160'],
            'routeOrigin' => ['required', 'string', 'max:160'],
            'routeDestination' => ['required', 'string', 'max:160'],
            'vehicleRegistration' => ['nullable', 'string', 'max:24'],
            'driverName' => ['nullable', 'string', 'max:160'],
            'seats' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'dutyPointId' => ['nullable', 'string', 'exists:emf_duty_points,id'],
            'lgaId' => ['nullable', 'string', 'exists:emf_lgas,id'],
            'departedAt' => ['nullable', 'date'],
            'expectedArrivalAt' => ['nullable', 'date', 'after_or_equal:departedAt'],
            'passengers' => ['required', 'array', 'min:1'],
            'passengers.*.passengerId' => ['nullable', 'string'],
            'passengers.*.fullName' => ['required', 'string', 'max:160'],
            'passengers.*.nin' => ['nullable', 'digits:11'],
            'passengers.*.phone' => ['nullable', 'string', 'max:32'],
            'passengers.*.seat' => ['nullable', 'string', 'max:16'],
        ];
    }
}
