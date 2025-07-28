<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AgentBookingRequest extends FormRequest
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
            'user_id' => 'required|integer|exists:users,id',
            'trip_id' => 'required|integer|exists:trips,id',
            'third_party_booking' => 'nullable|int',
            'selected_seat' => 'required|string',
            'trip_type' => 'required|int',
            'passengers' => 'nullable|array',
            'passengers.*.name' => 'required|string',
            'passengers.*.email' => 'required|string',
            'passengers.*.phone_number' => 'required|string',
            'passengers.*.gender' => 'required|string',
            'next_of_kin' => 'nullable|array',
            'next_of_kin.*.name' => 'required|string',
            'next_of_kin.*.relationship' => 'required|string',
            'next_of_kin.*.phone_number' => 'required|string',
            'amount_paid' => 'required|int',
            'payment_method' => 'required|in:wallet,paystack',
            'pin' => 'required_if:payment_method,wallet'
        ];
    }
}
