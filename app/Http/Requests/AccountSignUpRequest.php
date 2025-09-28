<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AccountSignUpRequest extends FormRequest
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
        $rules = [
            'full_name' => ['required', 'string', 'max:200'],
            'email' => ['nullable', 'string', 'email'],
            'phone_number' => ['required_if:email,null'],
            'user_category' => ['required', 'string', 'in:passenger,driver,agent'],
            'password' => ['required', 'string', 'confirmed', 'min:8'],
        ];

        if (app()->environment('production')) {
            $rules['email'][] = 'regex:/^[a-zA-Z0-9._%+-]+@(gmail\.com|yahoo\.com|outlook\.com|hotmail\.com)$/';
        }

        return $rules;
    }

    /**
     * Get custom error messages for specific fields.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'email.regex' => 'Please use a valid email address with one of the following domains: gmail.com, yahoo.com, outlook.com, hotmail.com.',
        ];
    }
}
