<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DriverInfoRequest extends FormRequest
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
            'user_id' => 'required|exists:users,id',
            'agent_id' => 'nullable|integer|exists:users,id',
            'transit_company_union_id' => 'required|integer',
            'vehicle_year' => 'required|integer|min:1900|max:' . now()->year,
            'vehicle_model' => 'required|string',
            'vehicle_color' => 'required|string',
            'plate_number' => 'required|string',
            'vehicle_type' => 'required|string',
            'vehicle_capacity' => 'required|integer|min:1',
            'profile_photo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'nin' => 'required|string',
            'nin_photo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'license_photo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'license_number' => 'required|string',
            'license_expiration_date' => 'required|date',
            'vehicle_insurance_photo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'vehicle_insurance_expiration_date' => 'required|date',
            'seats' => 'required',
            'seat_row' => 'required',
            'seat_column' => 'required',
            'union_states_chapter' => 'required|integer|exists:states,id'
        ];
    }
}
