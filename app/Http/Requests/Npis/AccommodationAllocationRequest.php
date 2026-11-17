<?php

namespace App\Http\Requests\Npis;

use Illuminate\Foundation\Http\FormRequest;

class AccommodationAllocationRequest extends FormRequest
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
            'allocationStatus' => ['required', 'string', 'in:requested,allocated,waitlisted,declined'],
            'hotelName' => ['nullable', 'string', 'max:200', 'required_if:allocationStatus,allocated'],
            'roomType' => ['nullable', 'string', 'max:120', 'required_if:allocationStatus,allocated'],
        ];
    }
}
