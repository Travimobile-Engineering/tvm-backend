<?php

namespace App\Http\Requests\Npis;

use Illuminate\Foundation\Http\FormRequest;

class StoreVerificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'channel' => ['required', 'string', 'in:sms,email'],
            'destination' => ['required', 'string', 'max:254'],
            'purpose' => ['nullable', 'string', 'in:registration,reopen'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'channel.in' => 'Choose SMS or email.',
            'destination.required' => 'Enter your phone number or email address.',
        ];
    }
}
