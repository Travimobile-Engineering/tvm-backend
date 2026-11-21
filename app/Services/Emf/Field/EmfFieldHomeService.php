<?php

namespace App\Services\Emf\Field;

use App\Models\Emf\Alert;
use App\Models\Emf\FieldCheck;
use App\Models\Emf\Manifest;
use App\Models\Emf\Notification;
use App\Models\Emf\Officer;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class EmfFieldHomeService
{
    public function __construct(private readonly EmfFieldPresenter $presenter) {}

    /**
     * @return array<string, mixed>
     */
    public function home(Officer $officer): array
    {
        $officer->loadMissing(['dutyPoint', 'state', 'lga']);
        $point = $officer->dutyPoint;
        $shiftEnd = $point ? $this->shiftEnd($point->shift_end) : null;
        $activeAlerts = $this->alertsQuery($officer);

        return [
            'officer' => [
                'officerId' => $officer->id,
                'fullName' => $officer->full_name,
                'rank' => $officer->rank,
                'serviceNumber' => $officer->service_number,
                'photoUrl' => $officer->photo_url,
                'status' => $officer->status,
                'stateCommand' => $officer->state?->name,
            ],
            'duty' => [
                'dutyPoint' => $point?->name,
                'dutyPointId' => $point?->id,
                'lga' => $officer->lga?->name,
                'state' => $officer->state?->name,
                'shiftStart' => $point?->shift_start,
                'shiftEnd' => $point?->shift_end,
                'shiftEndsAt' => $shiftEnd?->toIso8601String(),
                'shiftRemainingSeconds' => $shiftEnd instanceof Carbon ? max(0, (int) now()->diffInSeconds($shiftEnd, false)) : null,
            ],
            'quickActions' => [
                ['key' => 'report_incident', 'label' => 'Report incident', 'subtitle' => 'Problem at your post'],
                ['key' => 'call_ops_room', 'label' => 'Call ops room', 'subtitle' => $officer->ops_room ?? 'State command operations room', 'phone' => $officer->ops_room ?? config('emf.field.ops_room_phone')],
            ],
            'alerts' => [
                'active' => (clone $activeAlerts)->whereIn('status', ['open', 'acknowledged', 'assigned', 'escalated'])->count(),
                'resolved' => (clone $activeAlerts)->whereIn('status', ['dismissed', 'closed'])->count(),
                'unreadNotifications' => Notification::query()->where('officer_id', $officer->id)->where('status', 'unread')->count(),
            ],
            'upcomingManifests' => $this->upcomingQuery($officer)->limit(4)->get()
                ->map(fn (Manifest $manifest): array => [
                    ...$this->presenter->manifest($manifest),
                    'canStartCheck' => in_array($manifest->arrival_status, ['arrived', 'in_transit'], true) || $manifest->departed_at !== null,
                ])->values()->all(),
            'recentlyChecked' => $this->recentChecks($officer, 4),
            'serverTime' => now()->toIso8601String(),
        ];
    }

    /**
     * @return array{found: bool, query: string, message: string, manifest?: array<string, mixed>, latestCheck?: array<string, mixed>|null}
     */
    public function search(string $query, Officer $officer): array
    {
        $query = trim($query);
        $normalized = Str::upper(preg_replace('/[^A-Za-z0-9]/', '', $query) ?? '');

        $manifest = Manifest::query()
            ->where('state_code', $officer->state_code)
            ->where(function (Builder $builder) use ($query, $normalized): void {
                $builder->where('manifest_number', $query)
                    ->orWhere('vehicle_registration', $query)
                    ->orWhereRaw("REPLACE(REPLACE(UPPER(vehicle_registration), ' ', ''), '-', '') = ?", [$normalized]);
            })
            ->first();

        if (! $manifest) {
            return [
                'found' => false,
                'query' => $query,
                'message' => 'No NTEM manifest matches this plate today. No commercial bus carrying passengers without one is an offence.',
            ];
        }

        $check = FieldCheck::query()->where('manifest_id', $manifest->id)->latest('started_at')->first();

        return [
            'found' => true,
            'query' => $query,
            'message' => 'Manifest found.',
            'manifest' => $this->presenter->manifest($manifest, true),
            'latestCheck' => $check ? $this->presenter->check($check) : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function upcoming(Officer $officer, ?string $manifestId = null): array
    {
        if ($manifestId !== null) {
            $manifest = Manifest::query()->find($manifestId);

            if (! $manifest) {
                return ['manifest' => null];
            }

            return [
                'manifest' => [
                    ...$this->presenter->manifest($manifest, true),
                    'canStartCheck' => in_array($manifest->arrival_status, ['arrived', 'in_transit'], true) || $manifest->departed_at !== null,
                ],
            ];
        }

        return [
            'data' => $this->upcomingQuery($officer)->limit(20)->get()
                ->map(fn (Manifest $manifest): array => [
                    ...$this->presenter->manifest($manifest, true),
                    'canStartCheck' => in_array($manifest->arrival_status, ['arrived', 'in_transit'], true) || $manifest->departed_at !== null,
                ])->values()->all(),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function recentChecks(Officer $officer, int $limit = 10): array
    {
        return FieldCheck::query()
            ->where('officer_id', $officer->id)
            ->with(['manifest', 'officer', 'seats', 'incident'])
            ->latest('started_at')
            ->limit($limit)
            ->get()
            ->map(fn (FieldCheck $check): array => $this->presenter->check($check))
            ->values()
            ->all();
    }

    /**
     * @return Builder<Manifest>
     */
    private function upcomingQuery(Officer $officer): Builder
    {
        return Manifest::query()
            ->where('state_code', $officer->state_code)
            ->when($officer->duty_point_id, fn (Builder $query, string $point) => $query->where('duty_point_id', $point))
            ->whereNull('checked_at')
            ->whereIn('arrival_status', ['scheduled', 'in_transit', 'arrived'])
            ->whereDoesntHave('fieldChecks', fn (Builder $query) => $query->whereIn('status', ['in_progress', 'ready', 'on_hold']))
            ->with(['dutyPoint', 'passengers', 'trackingEvents'])
            ->orderBy('expected_arrival_at');
    }

    /**
     * @return Builder<Alert>
     */
    private function alertsQuery(Officer $officer): Builder
    {
        return Alert::query()
            ->where('state_code', $officer->state_code)
            ->when($officer->duty_point_id, fn (Builder $query, string $point) => $query->where('duty_point_id', $point));
    }

    private function shiftEnd(string $shiftEnd): Carbon
    {
        $end = Carbon::parse(now()->toDateString().' '.$shiftEnd);

        if ($end->isPast()) {
            $end->addDay();
        }

        return $end;
    }
}
