<?php

namespace App\Services\Npis;

use App\Enum\Npis\ParticipantType;
use App\Enum\Npis\RegistrationSource;
use App\Enum\Npis\RegistrationStatus;
use App\Exceptions\NpisApiException;
use App\Models\Npis\AccommodationAllocation;
use App\Models\Npis\AccommodationRequest;
use App\Models\Npis\Itinerary;
use App\Models\Npis\ParticipantProfile;
use App\Models\Npis\ProgrammeInterest;
use App\Models\Npis\Registration;
use App\Models\Npis\VerifiedContact;
use App\Support\Npis\AccommodationValidator;
use App\Support\Npis\ItineraryValidator;
use App\Support\Npis\RegistrationValidator;
use App\Support\Npis\TagNumber;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class RegistrationService
{
    public function __construct(
        private readonly EventService $events,
        private readonly BadgeService $badges,
        private readonly MediaService $media,
        private readonly NotificationService $notifications,
        private readonly AuditService $audit,
        private readonly RegistrationValidator $validator,
        private readonly ItineraryValidator $itineraryValidator,
        private readonly AccommodationValidator $accommodationValidator,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function submit(VerifiedContact $contact, array $payload): Registration
    {
        if ($contact->verified_at === null) {
            throw NpisApiException::make(401, 'UNAUTHENTICATED', 'Verify your phone number or email before registering.');
        }

        $event = $this->events->current();
        $deadline = $event->deadline('registrationClosesAt');

        if ($deadline !== null && now()->greaterThan($deadline)) {
            throw NpisApiException::make(
                403,
                'REGISTRATION_CLOSED',
                'Registration closed on '.$this->displayDate(substr($deadline, 0, 10)).'. Contact the organizing committee.'
            );
        }

        $errors = $this->validator->validate($payload, $event);

        if ($errors !== []) {
            throw NpisApiException::validation('Some details need attention.', $errors);
        }

        $participantType = (string) $payload['participantType'];
        $profile = $this->normalizeProfile($payload['profile'], $participantType);

        return DB::transaction(function () use ($contact, $payload, $event, $participantType, $profile): Registration {
            $contact = VerifiedContact::query()->lockForUpdate()->findOrFail($contact->id);

            $existing = Registration::query()
                ->where('verified_contact_id', $contact->id)
                ->whereIn('status', [
                    RegistrationStatus::Incomplete->value,
                    RegistrationStatus::PendingApproval->value,
                    RegistrationStatus::Approved->value,
                    RegistrationStatus::Rejected->value,
                ])
                ->lockForUpdate()
                ->first();

            if ($existing && $existing->status !== RegistrationStatus::Incomplete) {
                throw NpisApiException::make(
                    409,
                    'REGISTRATION_EXISTS',
                    'You are already registered. Open your registration to view or update it.',
                    [
                        'registrationId' => $existing->id,
                        'summitTagNumber' => $existing->summit_tag_number,
                    ]
                );
            }

            $duplicate = $this->findPossibleDuplicate($participantType, $profile, $existing?->id);
            $flags = [];

            if ($this->isUnlistedUnit($participantType, $profile)) {
                $flags[] = 'unlisted_unit';
            }

            if ($duplicate instanceof Registration) {
                $flags[] = 'possible_duplicate';
            }

            $status = ((bool) $event->policy('approvalRequired', true) || $flags !== [])
                ? RegistrationStatus::PendingApproval
                : RegistrationStatus::Approved;

            $now = now();

            if ($existing) {
                $registration = $existing;
                $registration->fill([
                    'status' => $status,
                    'participant_type' => $participantType,
                    'review_flags' => $flags,
                    'duplicate_of_registration_id' => $duplicate?->id,
                    'submitted_at' => $now,
                    'version' => $registration->version + 1,
                    'approved_at' => $status === RegistrationStatus::Approved ? $now : null,
                ]);
                $registration->active_contact_key = $registration->verified_contact_id;
                $registration->save();
            } else {
                $tagVendor = $participantType === ParticipantType::Vendor->value;
                $tag = TagNumber::unique(
                    fn (string $candidate): bool => Registration::query()
                        ->withTrashed()
                        ->where('summit_tag_number', $candidate)
                        ->exists(),
                    $tagVendor
                );

                $registration = Registration::create([
                    'id' => 'reg_'.Str::lower((string) Str::ulid()),
                    'event_id' => $event->id,
                    'verified_contact_id' => $contact->id,
                    'active_contact_key' => $contact->id,
                    'summit_tag_number' => $tag,
                    'status' => $status,
                    'source' => RegistrationSource::Online,
                    'participant_type' => $participantType,
                    'protocol_category' => null,
                    'duplicate_of_registration_id' => $duplicate?->id,
                    'merged_into_registration_id' => null,
                    'review_flags' => $flags,
                    'version' => 1,
                    'submitted_at' => $now,
                    'approved_at' => $status === RegistrationStatus::Approved ? $now : null,
                ]);
            }

            $this->persistProfile($registration, $profile);
            $this->persistItinerary($registration, $payload['itinerary'] ?? null);
            $this->persistAccommodation($registration, $payload['accommodation'] ?? null);
            $this->persistInterests($registration, $payload['interests'] ?? null);
            $this->badges->ensureBadge($registration->fresh(['profile', 'badge']));

            if (isset($payload['photo'])) {
                $this->media->storePhoto($registration, (string) $payload['photo'], 'registrant');
            }

            $registration->load($this->responseRelations());

            $this->audit->record(
                [
                    'type' => 'participant',
                    'id' => $registration->id,
                    'name' => $registration->profile->full_name,
                    'role' => 'officer',
                ],
                'registration.submitted',
                'registration',
                $registration->id,
                sprintf(
                    '%s submitted %s registration %s',
                    $registration->profile->full_name,
                    $participantType,
                    $registration->summit_tag_number
                ),
                [
                    'source' => $registration->source->value,
                    'participantType' => $participantType,
                    'reviewFlags' => $flags,
                ],
                request(),
            );

            $this->notifications->queue(
                'registration_confirmation',
                $contact->channel,
                (string) $contact->destination,
                [
                    'name' => $registration->profile->full_name,
                    'tag' => $registration->summit_tag_number,
                    'status' => $status->value,
                ],
                'registration',
                $registration->id,
            );

            return $registration->fresh($this->responseRelations());
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function updateItinerary(Registration $registration, array $payload, mixed $ifMatch): Registration
    {
        $event = $registration->event ?? $this->events->current();
        $registration->loadMissing(['profile', 'itinerary', 'contact']);

        if (! $registration->isActive()) {
            throw NpisApiException::make(404, 'NOT_FOUND', 'No registration found.');
        }

        if ($registration->participant_type === ParticipantType::Vendor) {
            throw NpisApiException::make(409, 'VALIDATION_FAILED', "Vendors don't provide travel plans.");
        }

        if ($registration->status === RegistrationStatus::Incomplete) {
            throw NpisApiException::make(409, 'VALIDATION_FAILED', 'Complete your registration first.');
        }

        $deadline = $event->deadline('travelEditDeadline');
        if ($deadline !== null && now()->greaterThan($deadline)) {
            throw NpisApiException::make(
                409,
                'EDIT_WINDOW_CLOSED',
                'Travel changes closed on '.$this->displayDate(substr($deadline, 0, 10)).'. Contact the logistics desk for urgent changes.',
                ['deadline' => $deadline]
            );
        }

        $this->assertVersion($registration, $ifMatch);

        $errors = $this->itineraryValidator->validate($payload, $event);
        if ($errors !== []) {
            throw NpisApiException::validation('Some travel details need attention.', $errors);
        }

        return DB::transaction(function () use ($registration, $payload): Registration {
            $registration = Registration::query()->lockForUpdate()->findOrFail($registration->id);
            $this->persistItinerary($registration, $payload);
            $registration->version += 1;
            $registration->save();
            $registration->load($this->responseRelations());

            $this->audit->record(
                $registration,
                'registration.itinerary_updated',
                'registration',
                $registration->id,
                'Travel itinerary updated for '.$registration->profile->full_name.' ('.$registration->summit_tag_number.')',
                ['travelMode' => $payload['travelMode'] ?? null],
                request(),
            );

            $contact = $registration->contact;
            $this->notifications->sendTravelUpdated($registration, $contact->channel, (string) $contact->destination);

            return $registration->fresh($this->responseRelations());
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function updateAccommodation(Registration $registration, array $payload, mixed $ifMatch): Registration
    {
        $event = $registration->event ?? $this->events->current();
        $registration->loadMissing(['profile', 'accommodation', 'contact']);

        if (! $registration->isActive()) {
            throw NpisApiException::make(404, 'NOT_FOUND', 'No registration found.');
        }

        if ($registration->status === RegistrationStatus::Incomplete) {
            throw NpisApiException::make(409, 'VALIDATION_FAILED', 'Complete your registration first.');
        }

        if (! $this->isAccommodationEligible($registration)) {
            throw NpisApiException::make(
                409,
                'VALIDATION_FAILED',
                $registration->participant_type === ParticipantType::Vendor
                    ? 'Vendors are not provided accommodation.'
                    : 'Accommodation options are not offered to police officers.'
            );
        }

        $deadline = $event->deadline('accommodationEditDeadline');
        if ($deadline !== null && now()->greaterThan($deadline)) {
            throw NpisApiException::make(
                409,
                'EDIT_WINDOW_CLOSED',
                'Accommodation changes closed on '.$this->displayDate(substr($deadline, 0, 10)).'. Contact the logistics desk for urgent changes.',
                ['deadline' => $deadline]
            );
        }

        $this->assertVersion($registration, $ifMatch);

        $errors = $this->accommodationValidator->validate($payload, $event);
        if ($errors !== []) {
            throw NpisApiException::validation('Some accommodation details need attention.', $errors);
        }

        return DB::transaction(function () use ($registration, $payload): Registration {
            $registration = Registration::query()->lockForUpdate()->findOrFail($registration->id);
            $old = $registration->accommodation;
            $datesChanged = $old && (
                $old->check_in_date?->format('Y-m-d') !== ($payload['checkInDate'] ?? null)
                || $old->check_out_date?->format('Y-m-d') !== ($payload['checkOutDate'] ?? null)
            );

            $preference = (string) $payload['preference'];
            $allocationStatus = match (true) {
                $preference === 'self_arranged' => 'not_requested',
                ! $old || $old->preference !== 'organizer_arranged' || $datesChanged => 'requested',
                default => $old->allocation_status,
            };

            if ($preference === 'self_arranged'
                || $old?->preference !== 'organizer_arranged'
                || $datesChanged) {
                AccommodationAllocation::query()
                    ->where('accommodation_request_id', $old?->id)
                    ->delete();
            }

            $this->persistAccommodation($registration, $payload, $allocationStatus);
            $registration->version += 1;
            $registration->save();
            $registration->load($this->responseRelations());

            $this->audit->record(
                $registration,
                'registration.accommodation_updated',
                'registration',
                $registration->id,
                'Accommodation updated for '.$registration->profile->full_name.' ('.$registration->summit_tag_number.')',
                ['preference' => $preference, 'allocationStatus' => $allocationStatus],
                request(),
            );

            $contact = $registration->contact;
            $this->notifications->sendAccommodationUpdated($registration, $contact->channel, (string) $contact->destination);

            return $registration->fresh($this->responseRelations());
        });
    }

    public function assertVersion(Registration $registration, mixed $ifMatch): void
    {
        if ($ifMatch === null || trim((string) $ifMatch) === '') {
            throw NpisApiException::make(
                428,
                'VALIDATION_FAILED',
                'The If-Match header is required.',
                ['fields' => ['If-Match' => 'Required']]
            );
        }

        if ((int) trim((string) $ifMatch, '"') !== (int) $registration->version) {
            throw NpisApiException::make(
                412,
                'VERSION_CONFLICT',
                'Your registration was changed elsewhere. Reload to see the latest details, then try again.',
                ['currentVersion' => $registration->version]
            );
        }
    }

    public function certificateEligibility(Registration $registration): string
    {
        if ($registration->certificate) {
            return 'issued';
        }

        if ($registration->participant_type === ParticipantType::Vendor) {
            return 'not_eligible';
        }

        $event = $registration->event ?? $this->events->current();
        $minimum = (int) $event->policy('certificateMinDaysAttended', 2);
        $days = $registration->checkIns()->distinct()->count('event_date');

        return $registration->status === RegistrationStatus::Approved && $days >= $minimum
            ? 'eligible'
            : 'not_eligible';
    }

    /**
     * @return list<string>
     */
    public function responseRelations(): array
    {
        return [
            'event',
            'contact',
            'profile',
            'itinerary',
            'accommodation.allocation',
            'badge',
            'interests',
            'assignments',
            'checkIns',
            'certificate',
        ];
    }

    /**
     * @param  array<string, mixed>  $profile
     * @return array<string, mixed>
     */
    private function normalizeProfile(array $profile, string $participantType): array
    {
        $fullName = $this->collapseWhitespace((string) $profile['fullName']);
        $police = $participantType === 'delegate' && ($profile['sector'] ?? null) === 'police';
        $base = [
            'full_name' => $fullName,
            'full_name_normalized' => $this->normalizeName($fullName),
            'sector' => null,
            'rank_code' => null,
            'state_code' => null,
            'command_id' => null,
            'unit_id' => null,
            'unit_other_name' => null,
            'organization' => null,
            'organization_normalized' => null,
            'designation' => null,
            'vendor_service' => null,
            'vendor_service_other' => null,
        ];

        if ($participantType === 'vendor') {
            $organization = $this->nullableTrim($profile['organization'] ?? null);

            return array_merge($base, [
                'organization' => $organization,
                'organization_normalized' => $organization ? $this->normalizeName($organization) : null,
                'designation' => $this->nullableTrim($profile['designation'] ?? null),
                'vendor_service' => $profile['vendorService'],
                'vendor_service_other' => ($profile['vendorService'] ?? null) === 'other'
                    ? $this->nullableTrim($profile['vendorServiceOther'] ?? null)
                    : null,
            ]);
        }

        if ($police) {
            return array_merge($base, [
                'sector' => 'police',
                'rank_code' => $profile['rankCode'],
                'state_code' => $profile['stateCode'],
                'command_id' => $profile['commandId'],
                'unit_id' => $profile['unitId'] ?? null,
                'unit_other_name' => empty($profile['unitId'])
                    ? $this->nullableTrim($profile['unitOtherName'] ?? null)
                    : null,
                'designation' => $this->nullableTrim($profile['designation'] ?? null),
            ]);
        }

        $organization = $this->nullableTrim($profile['organization'] ?? null);

        return array_merge($base, [
            'sector' => $profile['sector'],
            'organization' => $organization,
            'organization_normalized' => $organization ? $this->normalizeName($organization) : null,
            'designation' => $this->nullableTrim($profile['designation'] ?? null),
        ]);
    }

    /**
     * @param  array<string, mixed>  $profile
     */
    private function persistProfile(Registration $registration, array $profile): void
    {
        ParticipantProfile::query()->updateOrCreate(
            ['registration_id' => $registration->id],
            $profile
        );

        $registration->unsetRelation('profile');
    }

    /**
     * @param  array<string, mixed>|null  $itinerary
     */
    private function persistItinerary(Registration $registration, ?array $itinerary): void
    {
        if ($itinerary === null) {
            Itinerary::query()->where('registration_id', $registration->id)->delete();
            $registration->unsetRelation('itinerary');

            return;
        }

        $arrival = $itinerary['arrival'] ?? [];
        $departure = $itinerary['departure'] ?? [];
        $flight = $itinerary['flight'] ?? null;

        Itinerary::query()->updateOrCreate(
            ['registration_id' => $registration->id],
            [
                'travel_mode' => $itinerary['travelMode'],
                'arrival_date' => $arrival['date'] ?? null,
                'arrival_time' => $arrival['time'] ?? null,
                'arrival_time_window' => $arrival['timeWindow'] ?? null,
                'arrival_plan_status' => $arrival['planStatus'],
                'departure_date' => $departure['date'] ?? null,
                'departure_time' => $departure['time'] ?? null,
                'departure_time_window' => $departure['timeWindow'] ?? null,
                'departure_plan_status' => $departure['planStatus'],
                'post_summit_plan' => $itinerary['postSummitPlan'],
                'departure_airport_code' => $flight['departureAirportCode'] ?? null,
                'flight_time' => $flight['flightTime'] ?? null,
                'flight_time_window' => $flight['flightTimeWindow'] ?? null,
                'flight_number' => isset($flight['flightNumber']) ? strtoupper((string) $flight['flightNumber']) : null,
                'timezone' => 'Africa/Lagos',
            ]
        );

        $registration->unsetRelation('itinerary');
    }

    /**
     * @param  array<string, mixed>|null  $accommodation
     */
    private function persistAccommodation(Registration $registration, ?array $accommodation, ?string $forcedStatus = null): void
    {
        if ($accommodation === null) {
            AccommodationAllocation::query()
                ->whereIn('accommodation_request_id', AccommodationRequest::query()
                    ->where('registration_id', $registration->id)
                    ->pluck('id'))
                ->delete();
            AccommodationRequest::query()->where('registration_id', $registration->id)->delete();
            $registration->unsetRelation('accommodation');

            return;
        }

        $preference = (string) $accommodation['preference'];
        $allowance = $preference === 'self_arranged' && ($accommodation['allowanceRequested'] ?? false) === true;
        $account = $allowance ? ($accommodation['payoutAccount'] ?? null) : null;
        $status = $forcedStatus ?? ($preference === 'organizer_arranged' ? 'requested' : 'not_requested');

        AccommodationRequest::query()->updateOrCreate(
            ['registration_id' => $registration->id],
            [
                'preference' => $preference,
                'check_in_date' => $preference === 'organizer_arranged' ? ($accommodation['checkInDate'] ?? null) : null,
                'check_out_date' => $preference === 'organizer_arranged' ? ($accommodation['checkOutDate'] ?? null) : null,
                'special_needs' => $this->nullableTrim($accommodation['specialNeeds'] ?? null),
                'allowance_requested' => $allowance,
                'payout_bank_code' => $account['bankCode'] ?? null,
                'payout_account_number' => $account['accountNumber'] ?? null,
                'payout_account_name' => isset($account['accountName'])
                    ? $this->collapseWhitespace((string) $account['accountName'])
                    : null,
                'allocation_status' => $status,
            ]
        );

        $registration->unsetRelation('accommodation');
    }

    /**
     * @param  array<string, mixed>|null  $interests
     */
    private function persistInterests(Registration $registration, ?array $interests): void
    {
        if ($interests === null) {
            ProgrammeInterest::query()->where('registration_id', $registration->id)->delete();
            $registration->unsetRelation('interests');

            return;
        }

        ProgrammeInterest::query()->updateOrCreate(
            ['registration_id' => $registration->id],
            [
                'track' => $interests['track'],
                'transport_subsectors' => array_values($interests['transportSubsectors'] ?? []),
            ]
        );

        $registration->unsetRelation('interests');
    }

    /**
     * @param  array<string, mixed>  $profile
     */
    private function findPossibleDuplicate(string $participantType, array $profile, ?string $excludeId): ?Registration
    {
        $query = Registration::query()
            ->where('participant_type', $participantType)
            ->where('status', '!=', RegistrationStatus::Cancelled->value)
            ->whereNull('merged_into_registration_id')
            ->when($excludeId, fn ($builder) => $builder->where('id', '!=', $excludeId))
            ->whereHas('profile', function ($builder) use ($profile): void {
                $builder->where('full_name_normalized', $profile['full_name_normalized']);

                if ($profile['sector'] === 'police') {
                    $builder->where('rank_code', $profile['rank_code'])
                        ->where('command_id', $profile['command_id']);
                } else {
                    $builder->where('organization_normalized', $profile['organization_normalized']);
                }
            });

        return $query->first();
    }

    /**
     * @param  array<string, mixed>  $profile
     */
    private function isUnlistedUnit(string $participantType, array $profile): bool
    {
        return $participantType === 'delegate'
            && ($profile['sector'] ?? null) === 'police'
            && empty($profile['unit_id']);
    }

    private function isAccommodationEligible(Registration $registration): bool
    {
        $profile = $registration->profile;

        return $registration->participant_type === ParticipantType::Delegate
            && $profile !== null
            && $profile->sector !== null
            && $profile->sector !== 'police';
    }

    private function normalizeName(string $value): string
    {
        return strtolower((string) preg_replace('/[^a-zA-Z]/', '', $value));
    }

    private function collapseWhitespace(string $value): string
    {
        return trim((string) preg_replace('/\s+/', ' ', $value));
    }

    private function nullableTrim(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = $this->collapseWhitespace((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }

    private function displayDate(string $date): string
    {
        try {
            return Carbon::parse($date)->format('D, j M Y');
        } catch (Throwable) {
            return $date;
        }
    }
}
