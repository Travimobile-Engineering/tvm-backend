<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class JobApplyRequest extends FormRequest
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
            'job_id' => ['required'],
            'full_name' => ['required'],
            'dob' => ['required'],
            'gender' => ['required'],
            'state_of_origin' => ['required'],
            'address' => ['required'],
            'phone' => ['required'],
            'email' => ['required', 'email'],
            'state_applying_for' => ['required'],
            'highest_level_of_education' => ['required'],
            'field_of_study' => ['required'],
            'resume' => ['required', 'mimes:pdf,docx,txt'],
            'cover_letter' => ['required', 'mimes:pdf,docx,txt'],
        ];
    }
}
