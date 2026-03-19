<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class VehicleRequirementRequest extends FormRequest
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
            'management_type' => 'required|in:travi_hire,self_managed',
            'is_ac_available' => 'required|boolean',
            'vehicle_interior_images' => 'required|array|min:1',
            'vehicle_interior_images.*' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'vehicle_exterior_images' => 'required|array|min:1',
            'vehicle_exterior_images.*' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ];
    }
}
