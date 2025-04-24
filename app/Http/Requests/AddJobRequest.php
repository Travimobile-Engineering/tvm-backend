<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddJobRequest extends FormRequest
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
            'title' => ['required'],
            'type' => ['required', 'in:full-time,part-time,hybrid,remote,contract'],
            'deadline' => ['required'],
            'summary' => ['required'],
            'responsibilities' => ['required'],
            'requirement' => ['required'],
            'offer' => ['required'],
            
        ];
    }
}
