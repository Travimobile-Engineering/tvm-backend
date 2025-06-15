<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SetAvailabilityRequest extends FormRequest
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
            'is_available' => ['required', 'boolean'],
            'lng' => ['required', 'numeric'],
            'lat' => ['required', 'numeric'],
            'unavailable_dates' => ['required', 'array'],
            'unavailable_dates.*' => ['required', 'date_format:Y-m-d'],
        ];
    }

    public function messages(): array
    {
        return [
            'unavailable_dates.*.date_format' => 'Each unavailable date must be in the format YYYY-MM-DD.',
        ];
    }
}
