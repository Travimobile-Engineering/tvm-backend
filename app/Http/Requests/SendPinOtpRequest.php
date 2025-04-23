<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendPinOtpRequest extends FormRequest
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
            'user_id' => 'required|integer|exists:users,id',
            'method' => 'required|string|in:email,sms',
            'email' => 'required|email',
        ];
    }
}
