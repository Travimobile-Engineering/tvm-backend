<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddIncidentRequest extends FormRequest
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
            'category' => 'required|string',
            'type' => 'required|string',
            'date' => 'required',
            'time' => 'required',
            'state_id' => 'required|integer',
            'city' => 'required|string',
            'description' => 'required|string',
            'media' => ['nullable', 'file', 'mimes:jpg,jpeg,png,mp4,mpeg,avi,mov'],
        ];
    }
}
