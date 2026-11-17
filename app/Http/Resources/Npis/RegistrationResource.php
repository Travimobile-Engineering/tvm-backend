<?php

namespace App\Http\Resources\Npis;

use App\Services\Npis\BadgeService;
use App\Services\Npis\RegistrationService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RegistrationResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $permissions = $request->attributes->get('npis_permissions', []);
        $can = static fn (string $permission): bool => in_array('*', $permissions, true) || in_array($permission, $permissions, true);

        $badge = app(BadgeService::class)->contract($this->resource);
        $profile = $this->profile;
        $itinerary = $this->itinerary;
        $accommodation = $this->accommodation;
        $certificate = $this->certificate;

        return [
            'registrationId' => $this->id,
            'summitTagNumber' => $this->summit_tag_number,
            'status' => $this->status?->value ?? (string) $this->status,
            'source' => $this->source?->value ?? (string) $this->source,
            'participantType' => $this->participant_type?->value ?? (string) $this->participant_type,
            'badge' => $badge,
            'contact' => $can('contact:read') && $this->contact ? [
                'phone' => $this->contact->channel === 'sms' ? (string) $this->contact->destination : null,
                'email' => $this->contact->channel === 'email' ? (string) $this->contact->destination : null,
                'verifiedChannel' => $this->contact->channel,
                'verifiedAt' => $this->contact->verified_at?->toIso8601String(),
            ] : null,
            'profile' => [
                'fullName' => $profile?->full_name,
                'sector' => $profile?->sector,
                'rankCode' => $profile?->rank_code,
                'stateCode' => $profile?->state_code,
                'commandId' => $profile?->command_id,
                'unitId' => $profile?->unit_id,
                'unitOtherName' => $profile?->unit_other_name,
                'organization' => $profile?->organization,
                'designation' => $profile?->designation,
                'vendorService' => $profile?->vendor_service,
                'vendorServiceOther' => $profile?->vendor_service_other,
            ],
            'itinerary' => $can('travel:read') && $itinerary ? [
                'travelMode' => $itinerary->travel_mode,
                'arrival' => [
                    'date' => $itinerary->arrival_date?->format('Y-m-d'),
                    'time' => $itinerary->arrival_time,
                    'timeWindow' => $itinerary->arrival_time_window,
                    'planStatus' => $itinerary->arrival_plan_status,
                ],
                'departure' => [
                    'date' => $itinerary->departure_date?->format('Y-m-d'),
                    'time' => $itinerary->departure_time,
                    'timeWindow' => $itinerary->departure_time_window,
                    'planStatus' => $itinerary->departure_plan_status,
                ],
                'postSummitPlan' => $itinerary->post_summit_plan,
                'flight' => $itinerary->travel_mode === 'air' ? [
                    'departureAirportCode' => $itinerary->departure_airport_code,
                    'flightTime' => $itinerary->flight_time,
                    'flightTimeWindow' => $itinerary->flight_time_window,
                    'flightNumber' => $itinerary->flight_number,
                ] : null,
                'timezone' => $itinerary->timezone,
            ] : null,
            'accommodation' => $can('accommodation:read') && $accommodation ? [
                'preference' => $accommodation->preference,
                'checkInDate' => $accommodation->check_in_date?->format('Y-m-d'),
                'checkOutDate' => $accommodation->check_out_date?->format('Y-m-d'),
                'specialNeeds' => $accommodation->special_needs,
                'allowanceRequested' => (bool) $accommodation->allowance_requested,
                'payoutAccount' => $accommodation->allowance_requested ? [
                    'bankCode' => $accommodation->payout_bank_code,
                    'accountNumber' => $accommodation->payout_account_number,
                    'accountName' => $accommodation->payout_account_name,
                ] : null,
                'allocationStatus' => $accommodation->allocation_status,
                'allocation' => $accommodation->allocation ? [
                    'hotelName' => $accommodation->allocation->hotel_name,
                    'roomType' => $accommodation->allocation->room_type,
                    'confirmedAt' => $accommodation->allocation->confirmed_at?->toIso8601String(),
                ] : null,
            ] : null,
            'interests' => $can('training:read') && $this->interests ? [
                'track' => $this->interests->track,
                'transportSubsectors' => $this->interests->transport_subsectors ?? [],
            ] : null,
            'reviewFlags' => $this->review_flags ?? [],
            'duplicateOfRegistrationId' => $this->duplicate_of_registration_id,
            'trainingAssignments' => $can('training:read')
                ? $this->assignments->map(fn ($assignment): array => [
                    'assignmentId' => $assignment->id,
                    'registrationId' => $assignment->registration_id,
                    'sessionId' => $assignment->session_id,
                    'assignedAt' => $assignment->assigned_at?->toIso8601String(),
                    'assignedBy' => $assignment->assigned_by_admin_id ? 'Organizer' : 'Organizer',
                ])->values()->all()
                : [],
            'attendance' => [
                'checkedInDates' => $this->checkIns
                    ->pluck('event_date')
                    ->map(fn ($date) => $date->format('Y-m-d'))
                    ->unique()
                    ->sort()
                    ->values()
                    ->all(),
            ],
            'certificate' => [
                'eligibility' => app(RegistrationService::class)->certificateEligibility($this->resource),
                'certificateId' => $certificate?->id,
            ],
            'submittedAt' => $this->submitted_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
            'version' => $this->version,
        ];
    }
}
