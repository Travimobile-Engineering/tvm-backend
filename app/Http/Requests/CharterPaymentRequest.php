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
            'email' => ['required', 'email'],
            'amount' => ['required', 'numeric'],
            'ticket_type' => ['required', 'string'],
            'pickup_location' => ['required', 'string', 'max:200'],
            'dropoff_location' => ['required', 'string', 'max:200'],
            'bus_stops' => ['required', 'array'],
            'luggage' => ['required', 'array'],
            'time' => ['required'],
            'date' => ['required'],
            'redirect_url' => ['required', 'url'],
        ];
    }
}
