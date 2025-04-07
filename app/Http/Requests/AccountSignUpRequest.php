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
        return [
            'full_name' => ['required', 'string', 'max:200'],
            'email' => ['string'],
            'phone_number' => ['required_if:email,null'],
            'user_category' => ['required', 'string', 'in:passenger,driver,agent'],
            'password' => ['required', 'string', 'confirmed', 'min:8']
        ];
    }
}
