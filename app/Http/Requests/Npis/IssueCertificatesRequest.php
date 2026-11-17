<?php

namespace App\Http\Requests\Npis;

use Illuminate\Foundation\Http\FormRequest;

class IssueCertificatesRequest extends FormRequest
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
            'registrationIds' => ['required', 'array', 'min:1', 'max:500'],
            'registrationIds.*' => ['string', 'max:64'],
        ];
    }
}
