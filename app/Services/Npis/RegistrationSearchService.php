<?php

namespace App\Services\Npis;

use App\Exceptions\NpisApiException;
use App\Models\Npis\AdminUser;
use App\Models\Npis\ProtocolCategory;
use App\Models\Npis\Registration;
use App\Support\Npis\Destination;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

class RegistrationSearchService
{
    /**
     * Filters that expose sensitive data and therefore require a permission.
     */
    public const FILTER_PERMISSIONS = [
        'travelMode' => 'travel:read',
        'arrivalDate' => 'travel:read',
        'arrivalWindow' => 'travel:read',
        'departureDate' => 'travel:read',
        'departureWindow' => 'travel:read',
        'planStatus' => 'travel:read',
        'airportCode' => 'travel:read',
        'postSummitPlan' => 'travel:read',
        'accommodationPreference' => 'accommodation:read',
        'allocationStatus' => 'accommodation:read',
        'accommodationAllowance' => 'accommodation:read',
        'stayNight' => 'accommodation:read',
        'track' => 'training:read',
        'subsector' => 'training:read',
        'sessionId' => 'training:read',
        'assigned' => 'training:read',
    ];

    public function __construct(private readonly EventService $events) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<Registration>
     */
    public function build(array $filters, AdminUser $admin): Builder
    {
        foreach ($filters as $filter => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            $permission = self::FILTER_PERMISSIONS[$filter] ?? null;

            if ($permission && ! $admin->hasPermission($permission)) {
                throw NpisApiException::make(
                    403,
                    'FORBIDDEN',
                    "Your role cannot filter by {$filter}.",
                    ['filter' => $filter, 'requiredPermission' => $permission]
                );
            }
        }

        $event = $this->events->current();
        $minimumAttendance = (int) $event->policy('certificateMinDaysAttended', 2);

        $query = Registration::query()
            ->with([
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
            ]);

        $status = $filters['status'] ?? null;

        if ($status) {
            $query->where('status', $status);
        } else {
            $query->where('status', '!=', 'cancelled');
        }

        if (! empty($filters['q'])) {
            $term = trim((string) $filters['q']);
            $hash = Destination::hash($term);

            $query->where(function (Builder $builder) use ($term, $hash): void {
                $builder->where('summit_tag_number', 'like', '%'.strtoupper($term).'%')
                    ->orWhereHas('profile', function (Builder $profile) use ($term): void {
                        $profile->where('full_name', 'like', '%'.$term.'%')
                            ->orWhere('organization', 'like', '%'.$term.'%');
                    })
                    ->orWhereHas('contact', function (Builder $contact) use ($hash): void {
                        $contact->where('destination_hash', $hash);
                    });
            });
        }

        foreach (['participantType' => 'participant_type'] as $filter => $column) {
            if (! empty($filters[$filter])) {
                $query->where($column, $filters[$filter]);
            }
        }

        if (! empty($filters['sector'])) {
            $query->whereHas('profile', fn (Builder $builder) => $builder->where('sector', $filters['sector']));
        }

        if (! empty($filters['vendorService'])) {
            $query->whereHas('profile', fn (Builder $builder) => $builder->where('vendor_service', $filters['vendorService']));
        }

        if (! empty($filters['protocolCategory'])) {
            $query->where(function (Builder $builder) use ($filters): void {
                $builder->where('protocol_category', $filters['protocolCategory'])
                    ->orWhereHas('badge', fn (Builder $badge) => $badge->where('category', $filters['protocolCategory']));
            });
        }

        if (! empty($filters['reviewFlag'])) {
            $query->whereJsonContains('review_flags', $filters['reviewFlag']);
        }

        if (! empty($filters['stateCode'])) {
            $query->whereHas('profile', fn (Builder $builder) => $builder->where('state_code', $filters['stateCode']));
        }

        if (! empty($filters['commandId'])) {
            $query->whereHas('profile', fn (Builder $builder) => $builder->where('command_id', $filters['commandId']));
        }

        if (! empty($filters['unitId'])) {
            $query->whereHas('profile', function (Builder $builder) use ($filters): void {
                if ($filters['unitId'] === 'unlisted') {
                    $builder->whereNull('unit_id')->whereNotNull('unit_other_name');
                } else {
                    $builder->where('unit_id', $filters['unitId']);
                }
            });
        }

        if (! empty($filters['photo'])) {
            if ($filters['photo'] === 'yes') {
                $query->whereHas('badge', fn (Builder $builder) => $builder->whereNotNull('photo_url'));
            } elseif ($filters['photo'] === 'no') {
                $query->whereDoesntHave('badge', fn (Builder $builder) => $builder->whereNotNull('photo_url'));
            } else {
                $required = ProtocolCategory::query()
                    ->where('photo_required', true)
                    ->pluck('code')
                    ->all();
                $query->whereHas('badge', function (Builder $builder) use ($required): void {
                    $builder->whereNull('photo_url')->whereIn('category', $required);
                });
            }
        }

        if (! empty($filters['travelMode'])) {
            $query->whereHas('itinerary', fn (Builder $builder) => $builder->where('travel_mode', $filters['travelMode']));
        }

        foreach ([
            'arrivalDate' => ['arrival_date', $filters['arrivalDate'] ?? null],
            'departureDate' => ['departure_date', $filters['departureDate'] ?? null],
        ] as $filter => [$column, $value]) {
            if (! empty($value)) {
                $query->whereHas('itinerary', function (Builder $builder) use ($column, $value): void {
                    $value === 'undecided'
                        ? $builder->whereNull($column)
                        : $builder->whereDate($column, $value);
                });
            }
        }

        foreach ([
            'arrivalWindow' => ['arrival_time', 'arrival_time_window'],
            'departureWindow' => ['departure_time', 'departure_time_window'],
        ] as $filter => [$timeColumn, $windowColumn]) {
            if (! empty($filters[$filter])) {
                $this->applyWindowFilter($query, $filters[$filter], $timeColumn, $windowColumn);
            }
        }

        if (! empty($filters['planStatus'])) {
            $query->whereHas('itinerary', function (Builder $builder) use ($filters): void {
                if ($filters['planStatus'] === 'undecided') {
                    $builder->where(function (Builder $inner): void {
                        $inner->where('travel_mode', 'undecided')
                            ->orWhere('arrival_plan_status', 'undecided')
                            ->orWhere('departure_plan_status', 'undecided');
                    });
                } elseif ($filters['planStatus'] === 'confirmed') {
                    $builder->where('arrival_plan_status', 'confirmed')
                        ->where('departure_plan_status', 'confirmed')
                        ->where('travel_mode', '!=', 'undecided');
                } else {
                    $builder->where('travel_mode', '!=', 'undecided')
                        ->where('arrival_plan_status', '!=', 'undecided')
                        ->where('departure_plan_status', '!=', 'undecided')
                        ->where(function (Builder $inner): void {
                            $inner->where('arrival_plan_status', '!=', 'confirmed')
                                ->orWhere('departure_plan_status', '!=', 'confirmed');
                        });
                }
            });
        }

        if (! empty($filters['airportCode'])) {
            $query->whereHas('itinerary', function (Builder $builder) use ($filters): void {
                $builder->where('travel_mode', 'air');
                $filters['airportCode'] === 'undecided'
                    ? $builder->whereNull('departure_airport_code')
                    : $builder->where('departure_airport_code', $filters['airportCode']);
            });
        }

        if (! empty($filters['postSummitPlan'])) {
            $query->whereHas('itinerary', fn (Builder $builder) => $builder->where('post_summit_plan', $filters['postSummitPlan']));
        }

        if (! empty($filters['accommodationPreference'])) {
            $query->whereHas('accommodation', fn (Builder $builder) => $builder->where('preference', $filters['accommodationPreference']));
        }

        if (! empty($filters['allocationStatus'])) {
            $query->whereHas('accommodation', fn (Builder $builder) => $builder->where('allocation_status', $filters['allocationStatus']));
        }

        if (! empty($filters['accommodationAllowance'])) {
            $query->whereHas('accommodation', fn (Builder $builder) => $builder->where('allowance_requested', $filters['accommodationAllowance'] === 'yes'));
        }

        if (! empty($filters['stayNight'])) {
            $night = $filters['stayNight'];
            $query->whereHas('accommodation', function (Builder $builder) use ($night): void {
                $builder->where('preference', 'organizer_arranged')
                    ->where('allocation_status', '!=', 'declined')
                    ->whereDate('check_in_date', '<=', $night)
                    ->whereDate('check_out_date', '>', $night);
            });
        }

        if (! empty($filters['track'])) {
            $query->whereHas('interests', fn (Builder $builder) => $builder->where('track', $filters['track']));
        }

        if (! empty($filters['subsector'])) {
            $query->whereHas('interests', fn (Builder $builder) => $builder->whereJsonContains('transport_subsectors', $filters['subsector']));
        }

        if (! empty($filters['sessionId'])) {
            $query->whereHas('assignments', fn (Builder $builder) => $builder->where('session_id', $filters['sessionId']));
        }

        if (! empty($filters['assigned'])) {
            if ($filters['assigned'] === 'yes') {
                $query->whereHas('assignments');
            } else {
                $query->whereHas('interests')->whereDoesntHave('assignments');
            }
        }

        if (! empty($filters['checkedIn'])) {
            $filters['checkedIn'] === 'yes'
                ? $query->whereHas('checkIns')
                : $query->whereDoesntHave('checkIns');
        }

        if (! empty($filters['checkedInDate'])) {
            $query->whereHas('checkIns', fn (Builder $builder) => $builder->whereDate('event_date', $filters['checkedInDate']));
        }

        if (! empty($filters['certificate'])) {
            $this->applyCertificateFilter($query, $filters['certificate'], $minimumAttendance);
        }

        if (! empty($filters['deliveryStatus'])) {
            $query->whereHas('certificate', fn (Builder $builder) => $builder->where('delivery_status', $filters['deliveryStatus']));
        }

        if (! empty($filters['submittedFrom'])) {
            $query->where('submitted_at', '>=', Carbon::parse($filters['submittedFrom'])->startOfDay());
        }

        if (! empty($filters['submittedTo'])) {
            $query->where('submitted_at', '<=', Carbon::parse($filters['submittedTo'])->endOfDay());
        }

        return $query;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{registrations: Collection<int, Registration>, pagination: array{page: int, pageSize: int, totalItems: int, totalPages: int}}
     */
    public function paginate(array $filters, AdminUser $admin): array
    {
        $page = max(1, (int) ($filters['page'] ?? 1));
        $pageSize = min(100, max(1, (int) ($filters['pageSize'] ?? 25)));
        $sort = (string) ($filters['sort'] ?? '-submittedAt');

        unset($filters['page'], $filters['pageSize'], $filters['sort']);

        $query = $this->build($filters, $admin);
        $total = (clone $query)->count();
        $this->applySort($query, $sort);

        $registrations = $query
            ->offset(($page - 1) * $pageSize)
            ->limit($pageSize)
            ->get();

        return [
            'registrations' => $registrations,
            'pagination' => [
                'page' => $page,
                'pageSize' => $pageSize,
                'totalItems' => $total,
                'totalPages' => max(1, (int) ceil($total / $pageSize)),
            ],
        ];
    }

    /**
     * @param  Builder<Registration>  $query
     */
    private function applySort(Builder $query, string $sort): void
    {
        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $field = ltrim($sort, '-');

        match ($field) {
            'fullName' => $query->orderByRaw(
                '(select full_name from npis_participant_profiles where npis_participant_profiles.registration_id = npis_registrations.id limit 1) '.$direction
            ),
            'arrivalDate' => $query->orderByRaw(
                '(select arrival_date from npis_itineraries where npis_itineraries.registration_id = npis_registrations.id limit 1) '.$direction
            ),
            'summitTagNumber' => $query->orderBy('summit_tag_number', $direction),
            default => $query->orderBy('submitted_at', $direction),
        };
    }

    /**
     * @param  Builder<Registration>  $query
     */
    private function applyWindowFilter(Builder $query, string $window, string $timeColumn, string $windowColumn): void
    {
        $ranges = [
            'early_morning' => ['05:00', '08:59'],
            'morning' => ['09:00', '11:59'],
            'afternoon' => ['12:00', '15:59'],
            'evening' => ['16:00', '19:59'],
            'night' => ['20:00', '04:59'],
        ];

        $query->whereHas('itinerary', function (Builder $builder) use ($window, $timeColumn, $windowColumn, $ranges): void {
            if ($window === 'undecided') {
                $builder->whereNull($timeColumn)->whereNull($windowColumn);

                return;
            }

            [$from, $to] = $ranges[$window];

            $builder->where(function (Builder $inner) use ($window, $timeColumn, $windowColumn, $from, $to): void {
                $inner->where($windowColumn, $window);

                if ($from <= $to) {
                    $inner->orWhere(function (Builder $time) use ($timeColumn, $from, $to): void {
                        $time->whereNotNull($timeColumn)->where($timeColumn, '>=', $from)->where($timeColumn, '<=', $to);
                    });
                } else {
                    $inner->orWhere(function (Builder $time) use ($timeColumn, $from): void {
                        $time->whereNotNull($timeColumn)->where($timeColumn, '>=', $from);
                    })->orWhere(function (Builder $time) use ($timeColumn, $to): void {
                        $time->whereNotNull($timeColumn)->where($timeColumn, '<=', $to);
                    });
                }
            });
        });
    }

    /**
     * @param  Builder<Registration>  $query
     */
    private function applyCertificateFilter(Builder $query, string $value, int $minimumAttendance): void
    {
        $attendanceSubquery = '(select count(distinct event_date) from npis_check_ins where npis_check_ins.registration_id = npis_registrations.id)';

        if ($value === 'issued') {
            $query->whereHas('certificate');

            return;
        }

        $query->whereDoesntHave('certificate');

        if ($value === 'eligible') {
            $query->where('status', 'approved')
                ->where('participant_type', 'delegate')
                ->whereRaw($attendanceSubquery.' >= ?', [$minimumAttendance]);

            return;
        }

        $query->where(function (Builder $builder) use ($attendanceSubquery, $minimumAttendance): void {
            $builder->where('participant_type', 'vendor')
                ->orWhere('status', '!=', 'approved')
                ->orWhereRaw($attendanceSubquery.' < ?', [$minimumAttendance]);
        });
    }
}
