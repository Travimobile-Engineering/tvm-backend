<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AirlineSignUpRequest extends FormRequest
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
            'airline' => ['required', 'string', 'max:200', 'unique:airlines,name'],
            'email' => ['required', 'string', 'email'],
            'manifest_submission_method' => ['required', 'string', Rule::in(['api', 'upload'])],
            'role' => ['nullable', 'string'],
            'first_name' => ['required', 'string', 'max:50'],
            'last_name' => ['required', 'string', 'max:50'],
            'phone_number' => ['required', 'string', 'max:50'],
            'password' => ['required', 'string', 'confirmed', 'min:8'],
        ];
    }
}
