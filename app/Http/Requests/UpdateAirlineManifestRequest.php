<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAirlineManifestRequest extends FormRequest
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
            'routing' => ['sometimes', 'array', 'min:2'],
            'routing.*' => ['required_with:routing', 'string', 'max:20'],
            'standby_location' => ['sometimes', 'nullable', 'string', 'max:100'],
            'aircraft_type' => ['sometimes', 'string', 'max:50'],
            'aircraft_registration' => ['sometimes', 'string', 'max:20'],
            'customer' => ['sometimes', 'string', 'max:100'],
            'planned_departure_time' => ['sometimes', 'nullable', 'date_format:Y-m-d H:i'],
            'flight_date' => ['sometimes', 'date'],
            'status' => ['sometimes', Rule::in(['draft', 'submitted', 'closed'])],
            'crews' => ['sometimes', 'array'],
            'crews.*.name' => ['required_with:crews', 'string', 'max:100'],
            'crews.*.role' => ['nullable', 'string', 'max:50'],
            'passengers' => ['sometimes', 'array', 'max:14'],
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
            'cargos' => ['sometimes', 'array', 'max:6'],
            'cargos.*.row_number' => ['required_with:cargos', 'integer', 'min:1', 'max:6'],
            'cargos.*.description' => ['required_with:cargos', 'string', 'max:150'],
            'cargos.*.pcs' => ['nullable', 'integer', 'min:0'],
            'cargos.*.company' => ['nullable', 'string', 'max:100'],
            'cargos.*.weight' => ['nullable', 'numeric', 'min:0'],
            'cargos.*.from_location' => ['nullable', 'string', 'max:20'],
            'cargos.*.to_location' => ['nullable', 'string', 'max:20'],
            'client_rep_name' => ['sometimes', 'nullable', 'string', 'max:150'],
            'time_received' => ['sometimes', 'nullable', 'date_format:H:i'],
            'time_closed' => ['sometimes', 'nullable', 'date_format:H:i'],
            'reason_for_delay' => ['sometimes', 'nullable', 'string', 'max:500'],
        ];
    }
}
