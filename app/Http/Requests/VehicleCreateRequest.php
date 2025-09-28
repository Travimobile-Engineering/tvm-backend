<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VehicleCreateRequest extends FormRequest
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
            'name' => 'required|string',
            'company_id' => 'required|int',
            'brand_id' => 'required|integer',
            'type_id' => 'required|integer',
            'plate_no' => 'required|string',
            'engine_no' => 'required|string',
            'chassis_no' => 'required|string',
            'color' => 'required|string',
            'seats' => 'required|string',
        ];
    }
}
