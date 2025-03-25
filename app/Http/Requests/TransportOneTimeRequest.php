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
            'vehicle_id' => ['required', 'integer', 'exists:vehicles,id'],
            'departure_id' => ['required', 'integer'],
            'destination_id' => ['required', 'integer'],
            'departure_date' => ['required', 'date'],
            'departure_time' => ['required', 'string'],
            'bus_type' => ['required', 'string'],
            'price' => ['required'],
        ];
    }
}
