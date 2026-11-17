<?php

namespace App\Http\Requests\Npis;

use Illuminate\Foundation\Http\FormRequest;

class AccessCodeRequest extends FormRequest
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
            'code' => ['required', 'string', 'min:8', 'max:64', 'regex:/^(?=.*[A-Za-z])(?=.*\d).+$/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.regex' => 'Use 8–64 characters with at least one letter and one number.',
        ];
    }
}
