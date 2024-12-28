<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransportOneTimeRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'departure' => ['required', 'string'],
            'destination' => ['required', 'string'],
            'departure_date' => ['required', 'date'],
            'departure_time' => ['required', 'string'],
            'repeat_trip' => ['required', 'string'],
            'bus_type' => ['required', 'string'],
            'vehicle_id' => ['required', 'int'],
            'transit_company_id' => ['required', 'int'],
            'route_id' => ['required', 'int'],
            'price' => ['required'],
            'bus_stops' => ['required', 'array']
        ];
    }
}
