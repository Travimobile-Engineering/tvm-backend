<?php

namespace App\Services\Npis;

use App\Enum\Npis\RegistrationStatus;
use App\Exceptions\NpisApiException;
use App\Models\Npis\AdminUser;
use App\Models\Npis\CheckIn;
use App\Models\Npis\Registration;
use App\Support\Npis\TagNumber;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;

class CheckInService
{
    public function __construct(
        private readonly EventService $events,
        private readonly BadgeService $badges,
        private readonly AuditService $audit,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function record(AdminUser $admin, string $tag, ?string $eventDate, string $method): array
    {
        $normalized = TagNumber::validate($tag);

        if (! $normalized) {
            throw NpisApiException::validation(
                'That is not a valid Summit Tag Number. Check the characters and try again — the format is NPIS26-XXXXX, or NPIS26-V-XXXXX for vendors.',
                ['summitTagNumber' => 'Invalid format or check character']
            );
        }

        $event = $this->events->current();
        $date = $eventDate ?? now('Africa/Lagos')->format('Y-m-d');

        if (! $event->isWithinEventDates($date)) {
            throw NpisApiException::make(
                422,
                'OUTSIDE_EVENT_DATES',
                'Check-in is only open on summit days ('.$event->start_date->format('Y-m-d').' to '.$event->end_date->format('Y-m-d').').',
                ['eventDate' => $date]
            );
        }

        $registration = Registration::query()
            ->with(['profile', 'badge', 'checkIns'])
            ->where('summit_tag_number', $normalized)
            ->first();

        if (! $registration) {
            throw NpisApiException::make(404, 'NOT_FOUND', "No registration found for {$normalized}. Ask to see their confirmation, or search by name.");
        }

        if ($registration->status !== RegistrationStatus::Approved) {
            $status = $registration->status?->value ?? (string) $registration->status;
            throw NpisApiException::make(
                409,
                'NOT_ELIGIBLE',
                'This registration is '.str_replace('_', ' ', $status).'. Send the '.($registration->participant_type?->value ?? 'delegate').' to the registration desk.',
                ['status' => $status]
            );
        }

        $existing = CheckIn::query()
            ->where('registration_id', $registration->id)
            ->whereDate('event_date', $date)
            ->first();

        $officer = $this->officerPayload($registration);

        if ($existing) {
            return ['checkIn' => $this->contract($existing), 'officer' => $officer, 'replayed' => true];
        }

        $checkIn = null;

        try {
            $checkIn = CheckIn::create([
                'id' => 'chk_'.Str::lower((string) Str::ulid()),
                'registration_id' => $registration->id,
                'summit_tag_number' => $registration->summit_tag_number,
                'event_date' => $date,
                'checked_in_at' => now(),
                'method' => $method === 'qr' ? 'qr' : 'tag_number',
                'recorded_by_admin_id' => $admin->id,
            ]);
        } catch (QueryException $exception) {
            $concurrent = CheckIn::query()
                ->where('registration_id', $registration->id)
                ->whereDate('event_date', $date)
                ->first();

            if ($concurrent) {
                return ['checkIn' => $this->contract($concurrent), 'officer' => $officer, 'replayed' => true];
            }

            throw $exception;
        }

        $this->audit->record(
            $admin,
            'check_in.recorded',
            'check_in',
            $checkIn->id,
            $registration->profile->full_name.' checked in for '.$date,
            ['summitTagNumber' => $normalized, 'method' => $checkIn->method],
            request(),
        );

        return ['checkIn' => $this->contract($checkIn), 'officer' => $officer, 'replayed' => false];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function latest(?string $eventDate): array
    {
        return CheckIn::query()
            ->when($eventDate, fn ($query) => $query->whereDate('event_date', $eventDate))
            ->orderByDesc('checked_in_at')
            ->limit(100)
            ->get()
            ->map(fn (CheckIn $checkIn): array => $this->contract($checkIn))
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function contract(CheckIn $checkIn): array
    {
        return [
            'checkInId' => $checkIn->id,
            'registrationId' => $checkIn->registration_id,
            'summitTagNumber' => $checkIn->summit_tag_number,
            'eventDate' => $checkIn->event_date->format('Y-m-d'),
            'checkedInAt' => $checkIn->checked_in_at->toIso8601String(),
            'method' => $checkIn->method,
            'recordedBy' => $checkIn->recorded_by_admin_id
                ? (AdminUser::query()->find($checkIn->recorded_by_admin_id)?->name ?? 'Organizer')
                : 'Organizer',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function officerPayload(Registration $registration): array
    {
        $badge = $this->badges->contract($registration);
        $profile = $registration->profile;

        return [
            'participantType' => $registration->participant_type?->value ?? (string) $registration->participant_type,
            'fullName' => $profile?->full_name,
            'sector' => $profile?->sector,
            'rankCode' => $profile?->rank_code,
            'commandId' => $profile?->command_id,
            'organization' => $profile?->organization,
            'vendorService' => $profile?->vendor_service,
            'vendorServiceOther' => $profile?->vendor_service_other,
            'status' => $registration->status?->value ?? (string) $registration->status,
            'badge' => $badge,
            'zones' => $badge['category']['zoneAccess'] ?? [],
            'missingPhotoWarning' => $badge['photoMissing'],
        ];
    }
}
