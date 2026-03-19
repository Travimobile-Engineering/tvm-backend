<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class TripBookingCreateRequest extends FormRequest
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
            'trip_id' => 'required',
            'third_party_booking' => 'nullable|int',
            'selected_seat' => 'required|string',
            'trip_type' => 'required|int',
            'travelling_with' => 'nullable|array',
            'travelling_with.*.name' => 'nullable|string',
            'travelling_with.*.email' => 'nullable|string',
            'travelling_with.*.phone_number' => 'nullable|string',
            'third_party_passenger_details' => 'nullable|array',
            'amount_paid' => 'nullable|int',
            'payment_method' => 'required|in:wallet,paystack',
        ];
    }
}
