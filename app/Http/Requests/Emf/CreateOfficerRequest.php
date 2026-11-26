<?php

namespace App\Http\Requests\Emf;

use Illuminate\Validation\Rule;

class CreateOfficerRequest extends EmfRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'serviceNumber' => ['required', 'string', 'max:32', 'unique:emf_officers,service_number'],
            'rank' => ['required', 'string', 'max:32'],
            'fullName' => ['required', 'string', 'max:160'],
            'email' => ['required', 'email', 'max:160', 'unique:emf_officers,email'],
            'phone' => ['required', 'string', 'max:32'],
            'unit' => ['nullable', 'string', 'max:160'],
            'stateCode' => ['required', 'string', 'exists:emf_states,code'],
            'lgaId' => ['nullable', 'string', 'exists:emf_lgas,id'],
            'dutyPointId' => ['nullable', 'string', 'exists:emf_duty_points,id'],
            'role' => ['required', 'string', Rule::in(array_keys((array) config('emf.roles', [])))],
            'language' => ['sometimes', 'in:en,ha,ig,yo'],
            'nin' => ['nullable', 'digits:11'],
            'division' => ['nullable', 'string', 'max:160'],
            'reportsTo' => ['nullable', 'string', 'max:160'],
            'opsRoom' => ['nullable', 'string', 'max:160'],
            'photoUrl' => ['nullable', 'url', 'max:2048'],
            'sendInvite' => ['sometimes', 'boolean'],
        ];
    }
}
