<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CharterPaymentRequest extends FormRequest
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
            'user_id' => ['required', 'exists:users,id'],
            'vehicle_id' => ['required', 'exists:vehicles,id'],
            'charter_id' => ['required', 'exists:charters,id'],
            'email' => ['required', 'email'],
            'number_of_vehicles' => ['required', 'integer', 'min:1'],
            'amount' => ['required', 'numeric'],
            'ticket_type' => ['required', 'string'],
            'departure_id' => ['required'],
            'destination_id' => ['required'],
            'bus_stops' => ['required'],
            'luggage' => ['required'],
            'date' => ['required'],
            'redirect_url' => ['required', 'url'],
        ];
    }
}
