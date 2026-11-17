<?php

namespace App\Services\Npis;

use App\Enum\Npis\RegistrationStatus;
use App\Exceptions\NpisApiException;
use App\Models\Npis\AdminUser;
use App\Models\Npis\Registration;
use App\Models\Npis\TrainingAssignment;
use App\Models\Npis\TrainingSession;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TrainingService
{
    public function __construct(
        private readonly EventService $events,
        private readonly AuditService $audit,
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function sessions(): array
    {
        $event = $this->events->current();

        return TrainingSession::query()
            ->where('event_id', $event->id)
            ->orderBy('session_date')
            ->orderBy('start_time')
            ->get()
            ->map(fn (TrainingSession $session): array => $this->contract($session))
            ->all();
    }

    /**
     * @param  list<string>  $registrationIds
     * @return array<string, mixed>
     */
    public function assign(AdminUser $admin, TrainingSession $session, array $registrationIds): array
    {
        return DB::transaction(function () use ($admin, $session, $registrationIds): array {
            $session = TrainingSession::query()->lockForUpdate()->findOrFail($session->id);
            $results = [];

            foreach ($registrationIds as $registrationId) {
                $registration = Registration::query()->find($registrationId);

                if (! $registration || $registration->status === RegistrationStatus::Cancelled || $registration->merged_into_registration_id) {
                    $results[] = ['registrationId' => $registrationId, 'outcome' => 'not_found', 'assignment' => null];

                    continue;
                }

                if ($registration->status !== RegistrationStatus::Approved) {
                    $results[] = ['registrationId' => $registrationId, 'outcome' => 'not_eligible', 'assignment' => null];

                    continue;
                }

                $existing = TrainingAssignment::query()
                    ->where('registration_id', $registrationId)
                    ->where('session_id', $session->id)
                    ->first();

                if ($existing) {
                    $results[] = [
                        'registrationId' => $registrationId,
                        'outcome' => 'already_assigned',
                        'assignment' => $this->assignmentContract($existing),
                    ];

                    continue;
                }

                $assigned = TrainingAssignment::query()->where('session_id', $session->id)->count();

                if ($assigned >= $session->capacity) {
                    $results[] = ['registrationId' => $registrationId, 'outcome' => 'session_full', 'assignment' => null];

                    continue;
                }

                $assignment = TrainingAssignment::create([
                    'id' => 'asg_'.Str::lower((string) Str::ulid()),
                    'registration_id' => $registrationId,
                    'session_id' => $session->id,
                    'assigned_at' => now(),
                    'assigned_by_admin_id' => $admin->id,
                ]);

                $results[] = [
                    'registrationId' => $registrationId,
                    'outcome' => 'assigned',
                    'assignment' => $this->assignmentContract($assignment),
                ];
            }

            $count = count(array_filter($results, fn (array $result): bool => $result['outcome'] === 'assigned'));

            if ($count > 0) {
                $this->audit->record(
                    $admin,
                    'training.assigned',
                    'training_assignment',
                    $session->id,
                    "Assigned {$count} officer(s) to “{$session->title}”",
                    [
                        'sessionId' => $session->id,
                        'registrationIds' => array_values(array_map(
                            fn (array $result): string => $result['registrationId'],
                            array_filter($results, fn (array $result): bool => $result['outcome'] === 'assigned')
                        )),
                    ],
                    request(),
                );
            }

            return [
                'results' => $results,
                'session' => $this->contract($session->fresh()),
            ];
        });
    }

    public function removeAssignment(AdminUser $admin, TrainingAssignment $assignment): void
    {
        DB::transaction(function () use ($admin, $assignment): void {
            $assignment = TrainingAssignment::query()->lockForUpdate()->findOrFail($assignment->id);

            $this->audit->record(
                $admin,
                'training.unassigned',
                'training_assignment',
                $assignment->id,
                "Removed {$assignment->registration_id} from {$assignment->session_id}",
                $assignment->only(['registration_id', 'session_id']),
                request(),
            );

            $assignment->delete();
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function createSession(AdminUser $admin, array $payload): TrainingSession
    {
        $event = $this->events->current();

        $session = TrainingSession::create([
            'id' => 'ses_'.strtolower((string) str()->ulid()),
            'event_id' => $event->id,
            'title' => $payload['title'],
            'track' => $payload['track'],
            'subsector' => $payload['subsector'] ?? null,
            'session_date' => $payload['date'],
            'start_time' => $payload['startTime'],
            'end_time' => $payload['endTime'],
            'room' => $payload['room'],
            'capacity' => (int) $payload['capacity'],
        ]);

        $this->audit->record(
            $admin,
            'training.session_created',
            'training_session',
            $session->id,
            'Created training session “'.$session->title.'”',
            ['track' => $session->track, 'capacity' => $session->capacity],
            request(),
        );

        return $session;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function updateSession(AdminUser $admin, TrainingSession $session, array $payload): TrainingSession
    {
        return DB::transaction(function () use ($admin, $session, $payload): TrainingSession {
            $session = TrainingSession::query()->lockForUpdate()->findOrFail($session->id);
            $assigned = TrainingAssignment::query()->where('session_id', $session->id)->count();

            if ((int) $payload['capacity'] < $assigned) {
                throw new NpisApiException(
                    422,
                    'VALIDATION_FAILED',
                    'Capacity cannot be lower than the number of assigned participants.',
                    ['fields' => ['capacity' => "At least {$assigned} is required."]]
                );
            }

            $session->fill([
                'title' => $payload['title'],
                'track' => $payload['track'],
                'subsector' => $payload['subsector'] ?? null,
                'session_date' => $payload['date'],
                'start_time' => $payload['startTime'],
                'end_time' => $payload['endTime'],
                'room' => $payload['room'],
                'capacity' => (int) $payload['capacity'],
            ])->save();

            $this->audit->record(
                $admin,
                'training.session_updated',
                'training_session',
                $session->id,
                'Updated training session “'.$session->title.'”',
                ['capacity' => $session->capacity],
                request(),
            );

            return $session->fresh();
        });
    }

    public function deleteSession(AdminUser $admin, TrainingSession $session): void
    {
        DB::transaction(function () use ($admin, $session): void {
            $session = TrainingSession::query()->lockForUpdate()->findOrFail($session->id);

            $this->audit->record(
                $admin,
                'training.session_deleted',
                'training_session',
                $session->id,
                'Deleted training session “'.$session->title.'”',
                [],
                request(),
            );

            TrainingAssignment::query()->where('session_id', $session->id)->delete();
            $session->delete();
        });
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function scheduleFor(Registration $registration): array
    {
        $sessionIds = $registration->assignments()->pluck('session_id');

        return TrainingSession::query()
            ->whereIn('id', $sessionIds)
            ->orderBy('session_date')
            ->orderBy('start_time')
            ->get()
            ->map(fn (TrainingSession $session): array => $this->contract($session))
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function contract(TrainingSession $session): array
    {
        return [
            'sessionId' => $session->id,
            'title' => $session->title,
            'track' => $session->track,
            'subsector' => $session->subsector,
            'date' => $session->session_date->format('Y-m-d'),
            'startTime' => $session->start_time,
            'endTime' => $session->end_time,
            'room' => $session->room,
            'capacity' => $session->capacity,
            'assignedCount' => $session->assignments()->count(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function assignmentContract(TrainingAssignment $assignment): array
    {
        return [
            'assignmentId' => $assignment->id,
            'registrationId' => $assignment->registration_id,
            'sessionId' => $assignment->session_id,
            'assignedAt' => $assignment->assigned_at->toIso8601String(),
            'assignedBy' => $assignment->assigned_by_admin_id
                ? (AdminUser::query()->find($assignment->assigned_by_admin_id)?->name ?? 'Organizer')
                : 'Organizer',
        ];
    }
}
