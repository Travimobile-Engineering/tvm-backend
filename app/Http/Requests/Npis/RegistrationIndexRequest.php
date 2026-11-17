<?php

namespace App\Http\Requests\Npis;

use Illuminate\Foundation\Http\FormRequest;

class RegistrationIndexRequest extends FormRequest
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
            'q' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'string', 'in:incomplete,pending_approval,approved,rejected,cancelled'],
            'participantType' => ['nullable', 'string', 'in:delegate,vendor'],
            'sector' => ['nullable', 'string', 'in:police,security_agency,government,transport,hospitality,private_sector,media,other'],
            'vendorService' => ['nullable', 'string', 'in:usher,caterer,cleaner,security,photographer,videographer,journalist,driver,decorator,other'],
            'protocolCategory' => ['nullable', 'string', 'max:4'],
            'photo' => ['nullable', 'string', 'in:yes,no,missing'],
            'reviewFlag' => ['nullable', 'string', 'in:possible_duplicate,unlisted_unit'],
            'stateCode' => ['nullable', 'string', 'max:8'],
            'commandId' => ['nullable', 'string', 'max:64'],
            'unitId' => ['nullable', 'string', 'max:64'],
            'travelMode' => ['nullable', 'string', 'in:air,road,rail,sea,undecided'],
            'arrivalDate' => ['nullable', 'string', 'max:20'],
            'arrivalWindow' => ['nullable', 'string', 'in:early_morning,morning,afternoon,evening,night,undecided'],
            'departureDate' => ['nullable', 'string', 'max:20'],
            'departureWindow' => ['nullable', 'string', 'in:early_morning,morning,afternoon,evening,night,undecided'],
            'planStatus' => ['nullable', 'string', 'in:undecided,preferred,confirmed'],
            'airportCode' => ['nullable', 'string', 'max:8'],
            'postSummitPlan' => ['nullable', 'string', 'in:leave_immediately,extend_stay,undecided'],
            'accommodationPreference' => ['nullable', 'string', 'in:organizer_arranged,self_arranged'],
            'allocationStatus' => ['nullable', 'string', 'in:not_requested,requested,allocated,waitlisted,declined'],
            'accommodationAllowance' => ['nullable', 'string', 'in:yes,no'],
            'stayNight' => ['nullable', 'date_format:Y-m-d'],
            'track' => ['nullable', 'string', 'in:transportation,hospitality,both'],
            'subsector' => ['nullable', 'string', 'in:road,air,sea,rail'],
            'sessionId' => ['nullable', 'string', 'max:64'],
            'assigned' => ['nullable', 'string', 'in:yes,no'],
            'checkedIn' => ['nullable', 'string', 'in:yes,no'],
            'checkedInDate' => ['nullable', 'date_format:Y-m-d'],
            'certificate' => ['nullable', 'string', 'in:not_eligible,eligible,issued'],
            'deliveryStatus' => ['nullable', 'string', 'in:queued,sent,delivered,failed'],
            'submittedFrom' => ['nullable', 'date_format:Y-m-d'],
            'submittedTo' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:submittedFrom'],
            'page' => ['nullable', 'integer', 'min:1'],
            'pageSize' => ['nullable', 'integer', 'min:1', 'max:100'],
            'sort' => ['nullable', 'string', 'max:64'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function filters(): array
    {
        return collect($this->validated())
            ->except(['page', 'pageSize', 'sort'])
            ->reject(fn ($value): bool => $value === null || $value === '')
            ->all();
    }
}
