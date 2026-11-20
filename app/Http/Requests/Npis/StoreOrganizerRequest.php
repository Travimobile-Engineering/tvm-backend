<?php

namespace App\Http\Requests\Npis;

use App\Support\NpisOrganizerPermissions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreOrganizerRequest extends FormRequest
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
            'name' => ['required', 'string', 'min:2', 'max:200'],
            'email' => ['required', 'string', 'email:rfc', 'max:200', Rule::unique('npis_admin_users', 'email')],
            'role' => ['required', 'string', Rule::in(array_keys((array) config('npis.organizer.roles', [])))],
            'additionalPermissions' => ['nullable', 'array', 'max:50'],
            'additionalPermissions.*' => ['string', Rule::in(NpisOrganizerPermissions::names())],
            'password' => ['required', 'string', 'max:128', Password::min(12)->mixedCase()->numbers()],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('email')) {
            $this->merge(['email' => strtolower(trim((string) $this->input('email')))]);
        }

        if ($this->has('name')) {
            $this->merge(['name' => trim((string) $this->input('name'))]);
        }
    }
}
