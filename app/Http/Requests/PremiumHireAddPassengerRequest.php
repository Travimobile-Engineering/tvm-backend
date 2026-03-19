<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class PremiumHireAddPassengerRequest extends FormRequest
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
            'user_id' => 'required|exists:users,id',
            'premium_hire_booking_id' => 'required|exists:premium_hire_bookings,id',
            'passengers' => 'required|array',
            'passengers.*.name' => 'required|string',
            'passengers.*.email' => 'required|email',
            'passengers.*.phone_number' => 'required|string',
            'passengers.*.gender' => 'required|in:male,female',
            'passengers.*.next_of_kin' => 'required|string',
            'passengers.*.next_of_kin_phone_number' => 'required|string',
        ];
    }
}
