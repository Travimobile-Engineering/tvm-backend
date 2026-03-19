<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AgentInfoRequest extends FormRequest
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
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'profile_photo' => ['nullable', 'image', 'mimes:png,jpg,jepg', 'max:2048'],
            'gender' => ['required', 'string'],
            'nin' => ['nullable', 'string'],
            'address' => ['required', 'string'],
            'next_of_kin_full_name' => ['required', 'string'],
            'next_of_kin_relationship' => ['required', 'string'],
            'next_of_kin_phone_number' => ['required', 'string'],
        ];
    }
}
