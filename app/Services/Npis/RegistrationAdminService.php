<?php

namespace App\Services\Npis;

use App\Enum\Npis\RegistrationStatus;
use App\Exceptions\NpisApiException;
use App\Models\Npis\AccommodationAllocation;
use App\Models\Npis\AdminUser;
use App\Models\Npis\Hotel;
use App\Models\Npis\Registration;
use App\Models\Npis\TrainingAssignment;
use App\Models\Npis\Unit;
use Illuminate\Support\Facades\DB;

class RegistrationAdminService
{
    public function __construct(
        private readonly AuditService $audit,
        private readonly NotificationService $notifications,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function review(AdminUser $admin, Registration $registration, array $payload): Registration
    {
        return DB::transaction(function () use ($admin, $registration, $payload): Registration {
            $registration = Registration::query()
                ->with(['profile', 'contact', 'assignments'])
                ->lockForUpdate()
                ->findOrFail($registration->id);

            $action = (string) $payload['action'];
            $before = $registration->status->value;

            if ($registration->status === RegistrationStatus::Cancelled && $action !== 'merge_duplicate') {
                throw NpisApiException::make(409, 'VALIDATION_FAILED', 'This registration has been cancelled.');
            }

            $becameApproved = false;
            $becameRejected = false;

            switch ($action) {
                case 'approve':
                    if ($registration->reviewFlagValues() !== []) {
                        throw NpisApiException::make(
                            409,
                            'VALIDATION_FAILED',
                            'Resolve the review flags before approving.',
                            ['reviewFlags' => $registration->reviewFlagValues()]
                        );
                    }

                    if ($registration->status === RegistrationStatus::Incomplete) {
                        throw NpisApiException::make(409, 'VALIDATION_FAILED', 'This legacy record is incomplete. The officer must complete it first.');
                    }

                    $registration->status = RegistrationStatus::Approved;
                    $registration->approved_at = now();
                    $registration->rejection_reason = null;
                    $becameApproved = true;
                    break;

                case 'reject':
                    $note = trim((string) ($payload['note'] ?? ''));
                    if ($note === '') {
                        throw NpisApiException::validation('Add a reason for the rejection.', ['note' => 'Required when rejecting']);
                    }

                    $registration->status = RegistrationStatus::Rejected;
                    $registration->rejection_reason = $note;
                    $registration->rejected_at = now();
                    $becameRejected = true;
                    break;

                case 'mark_not_duplicate':
                    $registration->removeReviewFlag('possible_duplicate');
                    $registration->duplicate_of_registration_id = null;
                    break;

                case 'merge_duplicate':
                    $targetId = $payload['mergeIntoRegistrationId'] ?? $registration->duplicate_of_registration_id;
                    $target = Registration::query()
                        ->where('id', $targetId)
                        ->where('id', '!=', $registration->id)
                        ->first();

                    if (! $target) {
                        throw NpisApiException::validation('Choose the registration to keep.', ['mergeIntoRegistrationId' => 'Unknown registration']);
                    }

                    $registration->status = RegistrationStatus::Cancelled;
                    $registration->cancelled_at = now();
                    $registration->merged_into_registration_id = $target->id;
                    $registration->review_flags = [];
                    $registration->active_contact_key = null;
                    TrainingAssignment::query()->where('registration_id', $registration->id)->delete();
                    break;

                case 'accept_unlisted_unit':
                    if (! empty($payload['unitId'])) {
                        $unit = Unit::query()
                            ->where('id', $payload['unitId'])
                            ->where('command_id', $registration->profile?->command_id)
                            ->first();

                        if (! $unit) {
                            throw NpisApiException::validation('Choose a unit in the officer\'s command.', ['unitId' => 'Not in command']);
                        }

                        $registration->profile->unit_id = $unit->id;
                        $registration->profile->unit_other_name = null;
                        $registration->profile->save();
                    }

                    $registration->removeReviewFlag('unlisted_unit');
                    break;

                default:
                    throw NpisApiException::validation('Unknown review action.', ['action' => 'Unknown']);
            }

            $registration->version += 1;
            $registration->save();

            $after = $registration->status->value;

            $this->audit->record(
                $admin,
                'registration.review.'.$action,
                'registration',
                $registration->id,
                $action.' — '.$registration->profile->full_name.' ('.$registration->summit_tag_number.')',
                ['before' => $before, 'after' => $after, 'note' => $payload['note'] ?? null],
                request(),
            );

            if ($becameApproved || $becameRejected) {
                $channel = $registration->contact->channel;
                $recipient = (string) $registration->contact->destination;

                if ($becameApproved) {
                    $this->notifications->sendApproval($registration, $channel, $recipient);
                } else {
                    $this->notifications->sendRejection($registration, $channel, $recipient, (string) $registration->rejection_reason);
                }
            }

            return $registration->fresh(['event', 'contact', 'profile', 'itinerary', 'accommodation.allocation', 'badge', 'interests', 'assignments', 'checkIns', 'certificate']);
        });
    }

    /**
     * Accommodation partners and their room types for the allocation dialog.
     *
     * @return list<array<string, mixed>>
     */
    public function hotels(): array
    {
        return Hotel::query()
            ->orderBy('name')
            ->get()
            ->map(fn (Hotel $hotel): array => [
                'hotelId' => $hotel->id,
                'name' => $hotel->name,
                'roomTypes' => $hotel->room_types ?? [],
                'isActive' => (bool) $hotel->is_active,
            ])
            ->all();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function allocate(AdminUser $admin, Registration $registration, array $payload): Registration
    {
        return DB::transaction(function () use ($admin, $registration, $payload): Registration {
            $registration = Registration::query()->lockForUpdate()->findOrFail($registration->id);
            $accommodation = $registration->accommodation;

            if (! $accommodation) {
                throw NpisApiException::make(404, 'NOT_FOUND', 'Registration not found.');
            }

            if ($accommodation->preference !== 'organizer_arranged') {
                throw NpisApiException::make(409, 'VALIDATION_FAILED', 'This delegate is arranging their own accommodation.');
            }

            $status = (string) $payload['allocationStatus'];
            $before = $accommodation->allocation_status;
            $accommodation->allocation_status = $status;
            $accommodation->save();

            if ($status === 'allocated') {
                AccommodationAllocation::query()->updateOrCreate(
                    ['accommodation_request_id' => $accommodation->id],
                    [
                        'hotel_name' => trim((string) $payload['hotelName']),
                        'room_type' => trim((string) $payload['roomType']),
                        'confirmed_at' => now(),
                        'allocated_by_admin_id' => $admin->id,
                    ]
                );
            } else {
                AccommodationAllocation::query()
                    ->where('accommodation_request_id', $accommodation->id)
                    ->delete();
            }

            $registration->version += 1;
            $registration->save();

            $this->audit->record(
                $admin,
                'accommodation.allocation_changed',
                'registration',
                $registration->id,
                'Accommodation '.$before.' → '.$status.' for '.$registration->profile->full_name,
                ['before' => $before, 'after' => $status, 'hotelName' => $payload['hotelName'] ?? null],
                request(),
            );

            return $registration->fresh(['event', 'contact', 'profile', 'itinerary', 'accommodation.allocation', 'badge', 'interests', 'assignments', 'checkIns', 'certificate']);
        });
    }
}
