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
            'category' => 'required',
            'type' => 'required',
            'date' => 'required',
            'time' => 'required',
            'location' => 'required',
            'description' => 'required',
            'media' => ['file', 'mimes:jpg,jpeg,png,mp4,mpeg,avi,mov'],
        ];
    }
}
