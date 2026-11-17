<?php

namespace App\Http\Requests\Npis;

use Illuminate\Foundation\Http\FormRequest;

class CheckInRequest extends FormRequest
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
            'summitTagNumber' => ['required', 'string', 'max:24'],
            'eventDate' => ['nullable', 'date_format:Y-m-d'],
            'method' => ['nullable', 'string', 'in:tag_number,qr'],
        ];
    }
}
