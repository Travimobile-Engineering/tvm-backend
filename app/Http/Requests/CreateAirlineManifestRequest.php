<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CreateAirlineManifestRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'airline_id' => ['required', 'integer', 'exists:airlines,id'],
            'routing' => ['required', 'array', 'min:2'],
            'routing.*' => ['required', 'string', 'max:20'],
            'standby_location' => ['nullable', 'string', 'max:100'],
            'aircraft_type' => ['required', 'string', 'max:50'],
            'aircraft_registration' => ['required', 'string', 'max:20'],
            'customer' => ['required', 'string', 'max:100'],
            'planned_departure_time' => ['nullable', 'date_format:Y-m-d H:i'],
            'flight_date' => ['required', 'date'],

            'crews' => ['nullable', 'array'],
            'crews.*.name' => ['required_with:crews', 'string', 'max:100'],
            'crews.*.role' => ['nullable', 'string', 'max:50'],

            'passengers' => ['nullable', 'array', 'max:14'],
            'passengers.*.row_number' => ['required_with:passengers', 'integer', 'min:1', 'max:14'],
            'passengers.*.name' => ['required_with:passengers', 'string', 'max:150'],
            'passengers.*.job' => ['nullable', 'string', 'max:100'],
            'passengers.*.company' => ['nullable', 'string', 'max:100'],
            'passengers.*.bag_pcs' => ['nullable', 'integer', 'min:0'],
            'passengers.*.bag_wt' => ['nullable', 'numeric', 'min:0'],
            'passengers.*.pax_wt' => ['nullable', 'numeric', 'min:0'],
            'passengers.*.from_location' => ['nullable', 'string', 'max:20'],
            'passengers.*.to_location' => ['nullable', 'string', 'max:20'],
            'passengers.*.is_special_cargo' => ['nullable', 'boolean'],
            'passengers.*.special_cargo_type' => ['nullable', 'string', 'max:50'],

            'cargos' => ['nullable', 'array', 'max:6'],
            'cargos.*.row_number' => ['required_with:cargos', 'integer', 'min:1', 'max:6'],
            'cargos.*.description' => ['required_with:cargos', 'string', 'max:150'],
            'cargos.*.pcs' => ['nullable', 'integer', 'min:0'],
            'cargos.*.company' => ['nullable', 'string', 'max:100'],
            'cargos.*.weight' => ['nullable', 'numeric', 'min:0'],
            'cargos.*.from_location' => ['nullable', 'string', 'max:20'],
            'cargos.*.to_location' => ['nullable', 'string', 'max:20'],

            'client_rep_name' => ['nullable', 'string', 'max:150'],
            'time_received' => ['nullable', 'date_format:H:i'],
            'time_closed' => ['nullable', 'date_format:H:i'],
            'reason_for_delay' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'airline_id.exists' => 'The selected airline does not exist.',
            'routing.min' => 'Routing must include at least two locations.',
            'passengers.max' => 'A manifest can have a maximum of 14 passenger rows.',
            'cargos.max' => 'A manifest can have a maximum of 6 cargo rows.',
            'passengers.*.row_number.max' => 'Passenger row number must be between 1 and 14.',
            'cargos.*.row_number.max' => 'Cargo row number must be between 1 and 6.',
        ];
    }
}
