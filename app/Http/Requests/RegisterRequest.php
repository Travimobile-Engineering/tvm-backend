<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
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
            'full_name' => 'required|string|max:255',
            'email' => 'nullable|string|email|max:255',
            'phone_number' => 'numeric',
            // 'contact' => 'required|string',
            'password' => 'required|string|min:8',
            'address' => 'nullable|string|max:255',
            'nin' => 'nullable|string',
            'verification_code' => 'nullable|numeric',
            'user_category' => 'nullable|int',
        ];
    }
}
