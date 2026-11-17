<?php

namespace App\Http\Requests\Npis;

use App\Services\Npis\EventService;
use App\Support\Npis\AccommodationValidator;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class UpdateAccommodationRequest extends FormRequest
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
            'preference' => ['required', 'string', 'in:organizer_arranged,self_arranged'],
            'checkInDate' => ['nullable', 'date_format:Y-m-d'],
            'checkOutDate' => ['nullable', 'date_format:Y-m-d'],
            'specialNeeds' => ['nullable', 'string', 'max:500'],
            'allowanceRequested' => ['nullable', 'boolean'],
            'payoutAccount' => ['nullable', 'array'],
            'payoutAccount.bankCode' => ['nullable', 'string', 'max:16'],
            'payoutAccount.accountNumber' => ['nullable', 'string', 'max:10'],
            'payoutAccount.accountName' => ['nullable', 'string', 'max:100'],
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
                $errors = app(AccommodationValidator::class)->validate($this->all(), $event);

                if ($errors !== []) {
                    throw ValidationException::withMessages($errors);
                }
            },
        ];
    }
}
