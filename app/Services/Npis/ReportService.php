<?php

namespace App\Services\Npis;

use App\Models\Npis\AdminUser;
use App\Models\Npis\Airport;
use App\Models\Npis\Certificate;
use App\Models\Npis\Command;
use App\Models\Npis\Itinerary;
use App\Models\Npis\ProtocolCategory;
use App\Models\Npis\Registration;
use App\Models\Npis\State;
use App\Models\Npis\SummitEvent;
use App\Models\Npis\TrainingSession;
use App\Models\Npis\Unit;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Throwable;

class ReportService
{
    private const array STATUS_LABELS = [
        'incomplete' => 'Incomplete',
        'pending_approval' => 'Pending approval',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'cancelled' => 'Cancelled',
    ];

    private const array PARTICIPANT_LABELS = ['delegate' => 'Delegate', 'vendor' => 'Vendor'];

    private const array SECTOR_LABELS = [
        'police' => 'Nigeria Police Force',
        'security_agency' => 'Other security or enforcement agency',
        'government' => 'Government ministry, department or regulator',
        'transport' => 'Transport sector',
        'hospitality' => 'Hospitality sector',
        'private_sector' => 'Other private sector',
        'media' => 'Media organization',
        'other' => 'Other',
    ];

    private const array VENDOR_LABELS = [
        'usher' => 'Usher',
        'caterer' => 'Caterer',
        'cleaner' => 'Cleaner',
        'security' => 'Security guard / bouncer',
        'photographer' => 'Photographer',
        'videographer' => 'Videographer',
        'journalist' => 'Journalist',
        'driver' => 'Driver',
        'decorator' => 'Decorator',
        'other' => 'Other service',
    ];

    private const array TRAVEL_LABELS = ['air' => 'Air', 'road' => 'Road', 'rail' => 'Rail', 'sea' => 'Sea / waterway', 'undecided' => 'Undecided'];

    private const array TRACK_LABELS = ['transportation' => 'Transportation', 'hospitality' => 'Hospitality', 'both' => 'Both'];

    private const array SUBSECTOR_LABELS = ['road' => 'Road', 'air' => 'Air', 'sea' => 'Sea', 'rail' => 'Rail'];

    private const array ALLOCATION_LABELS = [
        'not_requested' => 'Not requested',
        'requested' => 'Requested — awaiting allocation',
        'allocated' => 'Allocated',
        'waitlisted' => 'Waitlisted',
        'declined' => 'Declined',
    ];

    private const array POST_SUMMIT_LABELS = [
        'leave_immediately' => 'Leave immediately after the summit',
        'extend_stay' => 'Extend stay in Abuja',
        'undecided' => 'Undecided',
    ];

    private const array WINDOW_LABELS = [
        'early_morning' => 'Early morning (05:00–08:59)',
        'morning' => 'Morning (09:00–11:59)',
        'afternoon' => 'Afternoon (12:00–15:59)',
        'evening' => 'Evening (16:00–19:59)',
        'night' => 'Night (20:00–04:59)',
        'undecided' => 'Undecided',
    ];

    public function __construct(
        private readonly RegistrationSearchService $search,
        private readonly EventService $events,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function registration(array $filters, AdminUser $admin): array
    {
        $registrations = $this->filtered($filters, $admin);
        $categories = ProtocolCategory::query()->get()->keyBy('code');
        $states = State::query()->pluck('name', 'code');
        $commands = Command::query()->pluck('name', 'id');
        $units = Unit::query()->pluck('name', 'id');

        return [
            'total' => $registrations->count(),
            'byStatus' => $this->buckets(
                $registrations,
                fn (Registration $registration): string => $registration->status->value,
                fn (string $key): string => self::STATUS_LABELS[$key] ?? $key,
                array_keys(self::STATUS_LABELS),
            ),
            'byParticipantType' => $this->buckets(
                $registrations,
                fn (Registration $registration): string => $registration->participant_type->value,
                fn (string $key): string => self::PARTICIPANT_LABELS[$key] ?? $key,
                array_keys(self::PARTICIPANT_LABELS),
            ),
            'bySector' => $this->buckets(
                $registrations,
                fn (Registration $registration): ?string => $registration->participant_type?->value === 'delegate'
                    ? $registration->profile?->sector
                    : null,
                fn (?string $key): string => self::SECTOR_LABELS[$key] ?? '—',
            ),
            'byVendorService' => $this->buckets(
                $registrations,
                fn (Registration $registration): ?string => $registration->participant_type?->value === 'vendor'
                    ? $registration->profile?->vendor_service
                    : null,
                fn (?string $key): string => self::VENDOR_LABELS[$key] ?? '—',
            ),
            'byProtocolCategory' => $this->buckets(
                $registrations,
                fn (Registration $registration): string => $registration->badge ? $registration->badge->category : 'DL',
                fn (string $key): string => isset($categories[$key])
                    ? $key.' · '.$categories[$key]->name
                    : $key,
                $categories->keys()->all(),
            ),
            'photosMissing' => $registrations->filter(function (Registration $registration) use ($categories): bool {
                $category = $categories[$registration->badge ? $registration->badge->category : 'DL'] ?? null;

                return $category !== null && $category->photo_required && (! $registration->badge || ! $registration->badge->photo_url);
            })->count(),
            'byState' => $this->buckets(
                $registrations,
                fn (Registration $registration): ?string => $registration->profile?->state_code,
                fn (?string $key): string => $states[$key] ?? '—',
            ),
            'byCommand' => $this->buckets(
                $registrations,
                fn (Registration $registration): ?string => $registration->profile?->command_id,
                fn (?string $key): string => $commands[$key] ?? '—',
            ),
            'byUnit' => $this->buckets(
                $registrations,
                function (Registration $registration): ?string {
                    $profile = $registration->profile;

                    return $profile && $profile->command_id
                        ? ($profile->unit_id ?? 'unlisted')
                        : null;
                },
                fn (?string $key): string => $key === 'unlisted'
                    ? 'Unlisted (pending review)'
                    : ($units[$key] ?? '—'),
            ),
            'reviewQueue' => $this->buckets(
                $registrations,
                fn (Registration $registration): array => $registration->review_flags ?? [],
                fn (?string $key): string => $key === 'unlisted_unit' ? 'Unlisted unit' : 'Possible duplicate',
                ['possible_duplicate', 'unlisted_unit'],
            ),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function travel(array $filters, AdminUser $admin): array
    {
        $registrations = $this->filtered($filters, $admin)->filter(fn (Registration $registration): bool => $registration->itinerary !== null);
        $windows = array_keys(self::WINDOW_LABELS);

        return [
            'total' => $registrations->count(),
            'byMode' => $this->buckets(
                $registrations,
                fn (Registration $registration): string => $registration->itinerary->travel_mode,
                fn (string $key): string => self::TRAVEL_LABELS[$key] ?? $key,
                array_keys(self::TRAVEL_LABELS),
            ),
            'arrivalsByDate' => $this->datesBuckets($registrations->map(fn (Registration $registration) => $registration->itinerary->arrival_date?->format('Y-m-d'))),
            'arrivalsByWindow' => $this->buckets(
                $registrations,
                fn (Registration $registration): string => $this->window($registration->itinerary->arrival_time, $registration->itinerary->arrival_time_window),
                fn (string $key): string => self::WINDOW_LABELS[$key] ?? $key,
                $windows,
            ),
            'departuresByDate' => $this->datesBuckets($registrations->map(fn (Registration $registration) => $registration->itinerary->departure_date?->format('Y-m-d'))),
            'departuresByWindow' => $this->buckets(
                $registrations,
                fn (Registration $registration): string => $this->window($registration->itinerary->departure_time, $registration->itinerary->departure_time_window),
                fn (string $key): string => self::WINDOW_LABELS[$key] ?? $key,
                $windows,
            ),
            'byAirport' => $this->buckets(
                $registrations,
                fn (Registration $registration): ?string => $registration->itinerary->travel_mode === 'air'
                    ? ($registration->itinerary->departure_airport_code ?? 'undecided')
                    : null,
                function (?string $key): string {
                    if ($key === 'undecided') {
                        return 'Undecided';
                    }
                    $airport = Airport::query()->find($key);

                    return $airport ? $airport->city.' ('.$airport->code.')' : (string) $key;
                },
            ),
            'byPostSummitPlan' => $this->buckets(
                $registrations,
                fn (Registration $registration): string => $registration->itinerary->post_summit_plan,
                fn (string $key): string => self::POST_SUMMIT_LABELS[$key] ?? $key,
                array_keys(self::POST_SUMMIT_LABELS),
            ),
            'pendingPlans' => $registrations->filter(fn (Registration $registration): bool => $this->planStatus($registration->itinerary) === 'undecided')->count(),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function accommodation(array $filters, AdminUser $admin): array
    {
        $registrations = $this->filtered($filters, $admin)->filter(fn (Registration $registration): bool => $registration->accommodation !== null);
        $organizer = $registrations->filter(fn (Registration $registration): bool => $registration->accommodation->preference === 'organizer_arranged'
            && $registration->accommodation->allocation_status !== 'declined');

        $roomNights = $organizer->flatMap(fn (Registration $registration): array => $this->roomNights(
            $registration->accommodation->check_in_date?->format('Y-m-d'),
            $registration->accommodation->check_out_date?->format('Y-m-d'),
        ));

        return [
            'total' => $registrations->count(),
            'byPreference' => $this->buckets(
                $registrations,
                fn (Registration $registration): string => $registration->accommodation->preference,
                fn (string $key): string => $key === 'organizer_arranged' ? 'Organizer-arranged' : 'Self-arranged',
                ['organizer_arranged', 'self_arranged'],
            ),
            'byAllocationStatus' => $this->buckets(
                $organizer,
                fn (Registration $registration): string => $registration->accommodation->allocation_status,
                fn (string $key): string => self::ALLOCATION_LABELS[$key] ?? $key,
                ['requested', 'allocated', 'waitlisted', 'declined'],
            ),
            'allowanceRequested' => $registrations->filter(fn (Registration $registration): bool => $registration->accommodation->allowance_requested)->count(),
            'checkInsByDate' => $this->datesBuckets($organizer->map(fn (Registration $registration) => $registration->accommodation->check_in_date?->format('Y-m-d'))),
            'checkOutsByDate' => $this->datesBuckets($organizer->map(fn (Registration $registration) => $registration->accommodation->check_out_date?->format('Y-m-d'))),
            'roomNightsByNight' => $this->buckets(
                $roomNights->map(fn (string $night): array => ['key' => $night]),
                fn (array $item): string => $item['key'],
                fn (string $key): string => 'Night of '.$this->displayDate($key),
                $roomNights->unique()->sort()->values()->all(),
            ),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function training(array $filters, AdminUser $admin): array
    {
        $registrations = $this->filtered($filters, $admin)->filter(fn (Registration $registration): bool => $registration->interests !== null);
        $event = $this->events->current();

        $sessions = TrainingSession::query()
            ->where('event_id', $event->id)
            ->orderBy('session_date')
            ->orderBy('start_time')
            ->get()
            ->map(fn ($session): array => [
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
            ]);

        return [
            'total' => $registrations->count(),
            'byTrack' => $this->buckets(
                $registrations,
                fn (Registration $registration): string => $registration->interests->track,
                fn (string $key): string => self::TRACK_LABELS[$key] ?? $key,
                array_keys(self::TRACK_LABELS),
            ),
            'bySubsector' => $this->buckets(
                $registrations,
                fn (Registration $registration): array => $registration->interests->transport_subsectors ?? [],
                fn (?string $key): string => self::SUBSECTOR_LABELS[$key] ?? (string) $key,
                array_keys(self::SUBSECTOR_LABELS),
            ),
            'unassigned' => $registrations->filter(fn (Registration $registration): bool => $registration->assignments->isEmpty())->count(),
            'sessions' => $sessions->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function attendance(array $filters, AdminUser $admin): array
    {
        $registrations = $this->filtered($filters, $admin);
        $event = $this->events->current();
        $dates = $this->eventDates($event);
        $ids = $registrations->pluck('id')->all();
        $certificates = Certificate::query()->whereIn('registration_id', $ids)->get();

        return [
            'total' => $registrations->count(),
            'checkInsByDate' => $this->buckets(
                $registrations,
                fn (Registration $registration): array => $registration->checkIns->pluck('event_date')->map(fn ($date) => $date->format('Y-m-d'))->unique()->values()->all(),
                fn (string $key): string => $this->displayDate($key),
                $dates,
            ),
            'byEligibility' => $this->buckets(
                $registrations,
                fn (Registration $registration): string => app(RegistrationService::class)->certificateEligibility($registration),
                fn (string $key): string => ['not_eligible' => 'Not eligible', 'eligible' => 'Eligible', 'issued' => 'Issued'][$key] ?? $key,
                ['eligible', 'issued', 'not_eligible'],
            ),
            'byDeliveryStatus' => $this->buckets(
                $certificates->map(fn ($certificate): array => ['key' => $certificate->delivery_status]),
                fn (array $item): string => $item['key'],
                fn (string $key): string => ucfirst($key),
                ['queued', 'sent', 'delivered', 'failed'],
            ),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, Registration>
     */
    public function filteredRegistrations(array $filters, AdminUser $admin): Collection
    {
        return $this->filtered($filters, $admin);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, Registration>
     */
    private function filtered(array $filters, AdminUser $admin): Collection
    {
        unset($filters['page'], $filters['pageSize'], $filters['sort']);

        return $this->search->build($filters, $admin)->get();
    }

    /**
     * @param  Collection<int, mixed>  $items
     * @param  callable(mixed): mixed  $keyResolver
     * @param  callable(mixed): string  $labelResolver
     * @param  list<string>|null  $order
     * @return list<array{key: string, label: string, count: int}>
     */
    private function buckets(Collection $items, callable $keyResolver, callable $labelResolver, ?array $order = null): array
    {
        $counts = [];

        foreach ($items as $item) {
            $keys = $keyResolver($item);
            $keys = is_array($keys) ? $keys : [$keys];

            foreach ($keys as $key) {
                $normalized = $key === null ? '—' : (string) $key;
                $counts[$normalized] = ($counts[$normalized] ?? 0) + 1;
            }
        }

        $keys = $order ?? array_keys($counts);

        return array_values(array_map(
            fn (string $key): array => [
                'key' => $key,
                'label' => $labelResolver($key),
                'count' => $counts[$key] ?? 0,
            ],
            $keys
        ));
    }

    /**
     * @param  iterable<string|null>  $values
     * @return list<array{key: string, label: string, count: int}>
     */
    private function datesBuckets(iterable $values): array
    {
        $counts = [];

        foreach ($values as $value) {
            $key = $value ?? 'undecided';
            $counts[$key] = ($counts[$key] ?? 0) + 1;
        }

        $keys = array_keys($counts);
        usort($keys, fn (string $a, string $b): int => $a === 'undecided' ? 1 : ($b === 'undecided' ? -1 : strcmp($a, $b)));

        return array_map(fn (string $key): array => [
            'key' => $key,
            'label' => $key === 'undecided' ? 'Undecided' : $this->displayDate($key),
            'count' => $counts[$key],
        ], $keys);
    }

    /**
     * @return list<string>
     */
    private function roomNights(?string $checkIn, ?string $checkOut): array
    {
        if (! $checkIn || ! $checkOut || $checkOut <= $checkIn) {
            return [];
        }

        $nights = [];
        $cursor = Carbon::parse($checkIn);
        $end = Carbon::parse($checkOut);

        while ($cursor->lessThan($end)) {
            $nights[] = $cursor->format('Y-m-d');
            $cursor->addDay();
        }

        return $nights;
    }

    /**
     * @return list<string>
     */
    private function eventDates(SummitEvent $event): array
    {
        $dates = [];
        $cursor = $event->start_date->copy();

        while ($cursor->lessThanOrEqualTo($event->end_date)) {
            $dates[] = $cursor->format('Y-m-d');
            $cursor->addDay();
        }

        return $dates;
    }

    private function window(?string $time, ?string $window): string
    {
        if ($time) {
            foreach ([
                'early_morning' => ['05:00', '08:59'],
                'morning' => ['09:00', '11:59'],
                'afternoon' => ['12:00', '15:59'],
                'evening' => ['16:00', '19:59'],
            ] as $key => [$from, $to]) {
                if ($time >= $from && $time <= $to) {
                    return $key;
                }
            }

            return 'night';
        }

        return $window ?? 'undecided';
    }

    private function planStatus(?Itinerary $itinerary): string
    {
        if (! $itinerary instanceof Itinerary) {
            return 'undecided';
        }

        if ($itinerary->travel_mode === 'undecided'
            || $itinerary->arrival_plan_status === 'undecided'
            || $itinerary->departure_plan_status === 'undecided') {
            return 'undecided';
        }

        return $itinerary->arrival_plan_status === 'confirmed' && $itinerary->departure_plan_status === 'confirmed'
            ? 'confirmed'
            : 'preferred';
    }

    private function displayDate(string $date): string
    {
        try {
            return Carbon::parse($date)->timezone('Africa/Lagos')->format('D, j M Y');
        } catch (Throwable) {
            return $date;
        }
    }
}
