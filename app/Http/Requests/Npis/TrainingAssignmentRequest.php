<?php

namespace App\Http\Requests\Npis;

use Illuminate\Foundation\Http\FormRequest;

class TrainingAssignmentRequest extends FormRequest
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
            'registrationIds' => ['required', 'array', 'min:1', 'max:200'],
            'registrationIds.*' => ['string', 'max:64'],
        ];
    }
}
