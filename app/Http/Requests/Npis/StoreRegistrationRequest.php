<?php

namespace App\Http\Requests\Npis;

use App\Services\Npis\EventService;
use App\Support\Npis\RegistrationValidator;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class StoreRegistrationRequest extends FormRequest
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
            'participantType' => ['required', 'string', 'in:delegate,vendor'],
            'profile' => ['required', 'array'],
            'profile.fullName' => ['required', 'string', 'max:100'],
            'profile.sector' => ['nullable', 'string', 'in:police,security_agency,government,transport,hospitality,private_sector,media,other'],
            'profile.rankCode' => ['nullable', 'string', 'max:16'],
            'profile.stateCode' => ['nullable', 'string', 'max:8'],
            'profile.commandId' => ['nullable', 'string', 'max:64'],
            'profile.unitId' => ['nullable', 'string', 'max:64'],
            'profile.unitOtherName' => ['nullable', 'string', 'max:120'],
            'profile.organization' => ['nullable', 'string', 'max:120'],
            'profile.designation' => ['nullable', 'string', 'max:120'],
            'profile.vendorService' => ['nullable', 'string', 'in:usher,caterer,cleaner,security,photographer,videographer,journalist,driver,decorator,other'],
            'profile.vendorServiceOther' => ['nullable', 'string', 'max:80'],
            'itinerary' => ['nullable', 'array'],
            'accommodation' => ['nullable', 'array'],
            'interests' => ['nullable', 'array'],
            'photo' => ['nullable', 'string'],
            'consent' => ['required', 'array'],
            'consent.dataProcessing' => ['accepted'],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $event = app(EventService::class)->current();
                $errors = app(RegistrationValidator::class)->validate($this->all(), $event);

                if ($errors !== []) {
                    throw ValidationException::withMessages($errors);
                }
            },
        ];
    }
}
