<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TripBookingUpdateRequest extends FormRequest
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
            'trip_id' => 'required|string',
                'third_party_booking' => 'nullable|int',
                'selected_seat' => 'required|string',
                'trip_type' => 'required|int',
                'travelling_with' => 'nullable|string',
                'third_party_passenger_details' => 'nullable|string',
                'amount_paid' => 'nullable|int',
                'payment_method' => 'nullable',
                'payment_status' => 'required|integer',
        ];
    }
}
