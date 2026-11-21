<?php

namespace App\Services\Npis;

use App\Models\Npis\AccommodationRequest;
use App\Models\Npis\AdminUser;
use App\Models\Npis\Certificate;
use App\Models\Npis\CheckIn;
use App\Models\Npis\Registration;

class OrganizerDashboardService
{
    public function __construct(
        private readonly ReportService $reports,
        private readonly EventService $events,
    ) {}

    /**
     * Role-aware overview for the organizer home screen. Sections the role
     * cannot read are omitted rather than returned empty.
     *
     * @return array<string, mixed>
     */
    public function dashboard(AdminUser $admin): array
    {
        $event = $this->events->current();
        $today = now($event->timezone);
        $registration = $this->reports->registration([], $admin);

        $dashboard = [
            'event' => [
                'eventId' => $event->id,
                'name' => $event->name,
                'startDate' => $event->start_date->format('Y-m-d'),
                'endDate' => $event->end_date->format('Y-m-d'),
                'venue' => ['name' => $event->venue_name, 'address' => $event->venue_address],
                'daysToEvent' => max(0, (int) $today->copy()->startOfDay()->diffInDays($event->start_date->copy()->startOfDay(), false)),
            ],
            'registrations' => [
                'total' => $registration['total'],
                'byStatus' => $registration['byStatus'],
                'byParticipantType' => $registration['byParticipantType'],
                'vendors' => $this->bucketCount($registration['byParticipantType'], 'vendor'),
                'photosMissing' => $registration['photosMissing'],
                'reviewQueue' => $registration['reviewQueue'],
                'oldestPendingApprovals' => $this->oldestPendingApprovals($event->id),
            ],
            'generatedAt' => now()->toIso8601String(),
        ];

        if ($admin->hasPermission('travel:read')) {
            $travel = $this->reports->travel([], $admin);

            $dashboard['travel'] = [
                'total' => $travel['total'],
                'pendingPlans' => $travel['pendingPlans'],
                'byMode' => $travel['byMode'],
                'arrivalsByDate' => $travel['arrivalsByDate'],
                'departuresByDate' => $travel['departuresByDate'],
            ];
        }

        if ($admin->hasPermission('accommodation:read')) {
            $accommodation = $this->reports->accommodation([], $admin);

            $dashboard['accommodation'] = [
                'total' => $accommodation['total'],
                'byAllocationStatus' => $accommodation['byAllocationStatus'],
                'allowanceRequested' => $accommodation['allowanceRequested'],
                'roomNightsByNight' => $accommodation['roomNightsByNight'],
                'nextToAllocate' => $this->nextToAllocate(),
            ];
        }

        if ($admin->hasPermission('training:read')) {
            $training = $this->reports->training([], $admin);
            $sessions = $training['sessions'];
            $capacity = (int) array_sum(array_column($sessions, 'capacity'));
            $assigned = (int) array_sum(array_column($sessions, 'assignedCount'));

            $dashboard['training'] = [
                'total' => $training['total'],
                'unassigned' => $training['unassigned'],
                'sessions' => count($sessions),
                'capacity' => $capacity,
                'assigned' => $assigned,
                'capacityUtilisation' => $capacity > 0 ? round($assigned / $capacity * 100, 1) : 0.0,
            ];
        }

        if ($admin->hasPermission('attendance:read')) {
            $attendance = $this->reports->attendance([], $admin);

            $dashboard['attendance'] = [
                'total' => $attendance['total'],
                'checkInsByDate' => $attendance['checkInsByDate'],
                'today' => $today->toDateString(),
                'checkInsToday' => CheckIn::query()->whereDate('event_date', $today->toDateString())->count(),
            ];
        }

        if ($admin->hasPermission('certificates:manage')) {
            $dashboard['certificates'] = [
                'total' => Certificate::query()->count(),
                'byDeliveryStatus' => $this->certificateDeliveryStatuses(),
            ];
        }

        return $dashboard;
    }

    /**
     * @param  list<array{key: string, label: string, count: int}>  $buckets
     */
    private function bucketCount(array $buckets, string $key): int
    {
        foreach ($buckets as $bucket) {
            if ($bucket['key'] === $key) {
                return $bucket['count'];
            }
        }

        return 0;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function oldestPendingApprovals(string $eventId): array
    {
        return Registration::query()
            ->where('event_id', $eventId)
            ->where('status', 'pending_approval')
            ->with('profile')
            ->orderBy('submitted_at')
            ->limit(5)
            ->get()
            ->map(fn (Registration $registration): array => [
                'registrationId' => $registration->id,
                'summitTagNumber' => $registration->summit_tag_number,
                'fullName' => $registration->profile?->full_name,
                'organization' => $registration->profile?->organization,
                'participantType' => $registration->participant_type->value,
                'submittedAt' => $registration->submitted_at?->toIso8601String(),
                'waitingDays' => $registration->submitted_at !== null
                    ? (int) $registration->submitted_at->diffInDays(now())
                    : 0,
            ])
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function nextToAllocate(): array
    {
        return AccommodationRequest::query()
            ->where('preference', 'organizer_arranged')
            ->where('allocation_status', 'requested')
            ->with('registration.profile')
            ->orderBy('check_in_date')
            ->limit(5)
            ->get()
            ->map(fn (AccommodationRequest $request): array => [
                'registrationId' => $request->registration_id,
                'summitTagNumber' => $request->registration?->summit_tag_number,
                'fullName' => $request->registration?->profile?->full_name,
                'organization' => $request->registration?->profile?->organization,
                'checkInDate' => $request->check_in_date?->format('Y-m-d'),
                'checkOutDate' => $request->check_out_date?->format('Y-m-d'),
                'specialNeeds' => $request->special_needs,
                'allowanceRequested' => (bool) $request->allowance_requested,
            ])
            ->all();
    }

    /**
     * @return list<array{key: string, label: string, count: int}>
     */
    private function certificateDeliveryStatuses(): array
    {
        $counts = Certificate::query()
            ->selectRaw('delivery_status, count(*) as aggregate')
            ->groupBy('delivery_status')
            ->pluck('aggregate', 'delivery_status');

        return array_map(
            fn (string $status): array => [
                'key' => $status,
                'label' => ucfirst($status),
                'count' => (int) ($counts[$status] ?? 0),
            ],
            ['queued', 'sent', 'delivered', 'failed']
        );
    }
}
