<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WatchListRequest extends FormRequest
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
            "full_name" => 'required',
            "phone" => 'required',
            "email" => 'required',
            "dob" => 'required',
            "state_of_origin" => 'required',
            "nin" => ['required', 'integer'],
            "investigation_officer" => 'required',
            "io_contact_number" => 'required',
            "alert_location" => 'required',
            "photo" => ['nullable', 'mimes:jpg,jpeg,png'],
            "documents" => ['nullable', 'mimes:png,jpeg,jpg,docx,txt,pdf'],
            "status" => ["nullable", "in:active,in custody,closed"]
        ];
    }
}
