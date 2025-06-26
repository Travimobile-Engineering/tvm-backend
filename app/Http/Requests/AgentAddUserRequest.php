<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AgentAddUserRequest extends FormRequest
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
            'phone_number' => 'required|string|unique:users,phone_number',
            'gender' => 'required|string',
            'nin' => 'nullable|string',
            'next_of_kin_full_name' => 'nullable|string',
            'next_of_kin_phone_number' => 'nullable|string',
            'next_of_kin_gender' => 'nullable|string',
            'next_of_kin_relationship' => 'nullable|string',
        ];
    }
}
