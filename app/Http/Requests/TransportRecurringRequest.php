<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransportRecurringRequest extends FormRequest
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
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date'],
            'trip_days' => ['required', 'array'],
            'reoccur_duration' => ['required', 'string'],
            'bus_type' => ['required', 'string'],
            'ticket_price' => ['required', 'string'],
            'bus_stops' => ['required', 'array']
        ];
    }
}
