<?php

namespace App\Services\Emf;

use App\Exceptions\NpisApiException;
use App\Models\Emf\AccessGrant;
use App\Models\Emf\Alert;
use App\Models\Emf\Device;
use App\Models\Emf\DutyPoint;
use App\Models\Emf\DutyRoster;
use App\Models\Emf\GrantTemplate;
use App\Models\Emf\Manifest;
use App\Models\Emf\Officer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class EmfAccessService
{
    public function __construct(private readonly EmfAuditService $audit) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array{data: list<array<string, mixed>>, pagination: array<string, int>}
     */
    public function officers(array $filters, Officer $viewer): array
    {
        $page = max(1, (int) ($filters['page'] ?? 1));
        $pageSize = min(100, max(1, (int) ($filters['pageSize'] ?? 25)));
        $query = Officer::query()
            ->where('state_code', $filters['stateCode'] ?? $viewer->state_code)
            ->when($filters['status'] ?? null, fn (Builder $q, string $status) => $q->where('status', $status))
            ->when($filters['search'] ?? null, function (Builder $q, string $search): void {
                $like = '%'.$search.'%';
                $q->where(fn (Builder $inner) => $inner->where('full_name', 'like', $like)->orWhere('service_number', 'like', $like)->orWhere('unit', 'like', $like));
            })
            ->when($filters['grant'] ?? null, function (Builder $q, string $grant): void {
                if ($grant === 'free') {
                    $q->whereDoesntHave('grants', fn (Builder $inner) => $inner->where('status', 'active')->where('ends_at', '>', now()));
                } elseif ($grant === 'active') {
                    $q->whereHas('grants', fn (Builder $inner) => $inner->where('status', 'active')->where('ends_at', '>', now()));
                }
            });

        $total = (clone $query)->count();
        $items = $query->with(['dutyPoint', 'lga'])->orderBy('full_name')->offset(($page - 1) * $pageSize)->limit($pageSize)
            ->get()->map(fn (Officer $officer): array => $this->officerContract($officer))->all();

        return ['data' => $items, 'pagination' => $this->pagination($page, $pageSize, $total)];
    }

    /**
     * @return array<string, mixed>
     */
    public function performance(Officer $officer): array
    {
        $checks = Manifest::query()->where('checked_by_officer_id', $officer->id);
        $alerts = Alert::query()->where('assigned_officer_id', $officer->id);
        $acknowledged = (clone $alerts)->whereNotNull('acknowledged_at')->get();
        $avgResponse = (int) round($acknowledged->avg(fn (Alert $a): int => (int) $a->detected_at->diffInSeconds($a->acknowledged_at)) ?? 0);
        $totalAlerts = (clone $alerts)->count();
        $confirmed = (clone $alerts)->whereIn('status', ['escalated', 'dismissed'])->count();

        $checksPerDay = collect(range(6, 0))->map(function (int $daysAgo) use ($officer): array {
            $date = now()->subDays($daysAgo)->toDateString();

            return [
                'date' => $date,
                'checks' => Manifest::query()->where('checked_by_officer_id', $officer->id)->whereDate('checked_at', $date)->count(),
            ];
        })->values()->all();

        return [
            'officer' => $this->officerContract($officer),
            'metrics' => [
                'checks' => (clone $checks)->count(),
                'hitsConfirmed' => $confirmed,
                'avgResponseSeconds' => $avgResponse,
                'avgResponseLabel' => $this->duration($avgResponse),
                'hitRate' => $totalAlerts > 0 ? round(($confirmed / $totalAlerts) * 100, 1) : 0,
            ],
            'checksPerDay' => $checksPerDay,
            'accessGrants' => $officer->grants()->orderByDesc('created_at')->limit(5)->get()->map(fn (AccessGrant $grant): array => [
                'grantNumber' => $grant->grant_number,
                'scope' => $grant->scope,
                'status' => $grant->status,
                'endsAt' => $grant->ends_at->toIso8601String(),
            ])->values()->all(),
            'hitsHandled' => (clone $alerts)->orderByDesc('detected_at')->limit(5)->get()->map(fn (Alert $alert): array => [
                'alertNumber' => $alert->alert_number,
                'type' => $alert->type,
                'outcome' => $alert->status,
                'responseSeconds' => $alert->acknowledged_at ? (int) $alert->detected_at->diffInSeconds($alert->acknowledged_at) : null,
                'date' => $alert->detected_at->toIso8601String(),
            ])->values()->all(),
        ];
    }

    public function setOfficerStatus(Officer $officer, string $status, Officer $actor, ?string $reason, Request $request): Officer
    {
        $officer->forceFill(['status' => $status])->save();
        $this->audit->record($actor, 'officer.status_changed', 'officer', $officer->id, 'Set '.$officer->full_name.' to '.$status, 'done', ['reason' => $reason], $request);

        return $officer->fresh();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{data: list<array<string, mixed>>, pagination: array<string, int>}
     */
    public function devices(array $filters): array
    {
        $page = max(1, (int) ($filters['page'] ?? 1));
        $pageSize = min(100, max(1, (int) ($filters['pageSize'] ?? 25)));
        $query = Device::query()->when($filters['status'] ?? 'pending', fn (Builder $q, string $status) => $q->where('status', $status));
        $total = (clone $query)->count();
        $items = $query->with('officer')->orderByDesc('requested_at')->offset(($page - 1) * $pageSize)->limit($pageSize)->get()
            ->map(fn (Device $device): array => [
                'deviceId' => $device->id,
                'officer' => $device->officer ? ['officerId' => $device->officer->id, 'fullName' => $device->officer->full_name, 'serviceNumber' => $device->officer->service_number] : null,
                'deviceName' => $device->device_name,
                'platform' => $device->platform,
                'status' => $device->status,
                'requestedAt' => $device->requested_at->toIso8601String(),
            ])->all();

        return ['data' => $items, 'pagination' => $this->pagination($page, $pageSize, $total)];
    }

    public function decideDevice(Device $device, string $status, Officer $officer, Request $request): Device
    {
        $device->forceFill([
            'status' => $status,
            'approved_by' => $officer->id,
            'approved_at' => now(),
        ])->save();

        $this->audit->record($officer, 'device.'.$status, 'device', $device->id, ucfirst($status).' device '.$device->device_name, 'done', [], $request);

        return $device->fresh();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{data: list<array<string, mixed>>, pagination: array<string, int>}
     */
    public function grants(array $filters, Officer $viewer): array
    {
        $page = max(1, (int) ($filters['page'] ?? 1));
        $pageSize = min(100, max(1, (int) ($filters['pageSize'] ?? 25)));
        $query = AccessGrant::query()
            ->whereHas('officer', fn (Builder $q) => $q->where('state_code', $viewer->state_code))
            ->when($filters['status'] ?? null, fn (Builder $q, string $status) => $q->where('status', $status))
            ->when($filters['search'] ?? null, function (Builder $q, string $search): void {
                $like = '%'.$search.'%';
                $q->where('grant_number', 'like', $like)->orWhereHas('officer', fn (Builder $inner) => $inner->where('full_name', 'like', $like)->orWhere('service_number', 'like', $like));
            });

        $total = (clone $query)->count();
        $items = $query->with(['officer', 'template'])->orderByDesc('created_at')->offset(($page - 1) * $pageSize)->limit($pageSize)
            ->get()->map(fn (AccessGrant $grant): array => $this->grantContract($grant))->all();

        return ['data' => $items, 'pagination' => $this->pagination($page, $pageSize, $total)];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function createGrant(array $payload, Officer $actor, Request $request): array
    {
        $officer = Officer::query()->find($payload['officerId']);

        if (! $officer) {
            throw NpisApiException::make(404, 'NOT_FOUND', 'Officer not found.');
        }

        $template = isset($payload['templateId']) ? GrantTemplate::query()->find($payload['templateId']) : null;
        $starts = isset($payload['startsAt']) ? Carbon::parse($payload['startsAt']) : now();
        $ends = isset($payload['endsAt']) ? Carbon::parse($payload['endsAt']) : $starts->copy()->addHours((int) ($template->duration_hours ?? 24));
        $modes = $payload['modes'] ?? $template->modes ?? [];
        $scope = $payload['scope'] ?? $template->scope ?? $officer->dutyPoint->name ?? 'All';

        $conflicts = $this->findConflicts($officer->id, $starts, $ends, $modes);

        if ($conflicts !== [] && empty($payload['overrideConflict'])) {
            throw NpisApiException::make(409, 'GRANT_CONFLICT', 'This officer already has an overlapping grant for one or more modes.', [
                'conflicts' => $conflicts,
            ]);
        }

        $grant = AccessGrant::create([
            'id' => 'gr_'.Str::lower((string) Str::ulid()),
            'grant_number' => $this->nextGrantNumber(),
            'officer_id' => $officer->id,
            'template_id' => $template?->id,
            'scope' => $scope,
            'modes' => $modes,
            'starts_at' => $starts,
            'ends_at' => $ends,
            'status' => 'active',
            'created_by' => $actor->id,
            'notes' => $payload['notes'] ?? null,
        ]);

        $this->audit->record($actor, 'grant.created', 'access_grant', $grant->id, 'Created grant '.$grant->grant_number.' for '.$officer->full_name, 'done', [], $request);

        return $this->grantContract($grant->fresh(['officer', 'template']));
    }

    /**
     * @return array<string, mixed>
     */
    public function showGrant(AccessGrant $grant): array
    {
        return $this->grantContract($grant->load(['officer', 'template']));
    }

    public function revokeGrant(AccessGrant $grant, string $code, ?string $reason, Officer $actor, Request $request): AccessGrant
    {
        if (! preg_match('/^\d{6}$/', $code)) {
            throw NpisApiException::validation('Enter the 6-digit confirmation code.', ['code' => 'Must be 6 digits']);
        }

        $grant->forceFill([
            'status' => 'revoked',
            'revoked_at' => now(),
            'revoke_reason' => $reason,
        ])->save();

        $this->audit->record($actor, 'grant.revoked', 'access_grant', $grant->id, 'Revoked grant '.$grant->grant_number, 'done', ['reason' => $reason], $request);

        return $grant->fresh();
    }

    /**
     * @param  list<string>  $officerIds
     * @param  list<string>  $modes
     * @return list<array<string, mixed>>
     */
    public function conflictPreview(array $officerIds, string $startsAt, string $endsAt, array $modes): array
    {
        $conflicts = [];
        $starts = Carbon::parse($startsAt);
        $ends = Carbon::parse($endsAt);

        foreach ($officerIds as $id) {
            foreach ($this->findConflicts($id, $starts, $ends, $modes) as $conflict) {
                $conflicts[] = $conflict;
            }
        }

        return $conflicts;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function templates(): array
    {
        return GrantTemplate::query()->orderBy('name')->get()->map(fn (GrantTemplate $template): array => [
            'templateId' => $template->id,
            'name' => $template->name,
            'description' => $template->description,
            'scope' => $template->scope,
            'modes' => $template->modes,
            'durationHours' => $template->duration_hours,
            'accessCount' => $template->access_count,
            'status' => $template->status,
        ])->all();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function updateTemplate(GrantTemplate $template, array $payload, Officer $officer, Request $request): GrantTemplate
    {
        $template->fill(array_filter([
            'name' => $payload['name'] ?? null,
            'description' => $payload['description'] ?? null,
            'scope' => $payload['scope'] ?? null,
            'modes' => $payload['modes'] ?? null,
            'duration_hours' => $payload['durationHours'] ?? null,
            'access_count' => $payload['accessCount'] ?? null,
        ], static fn ($value): bool => $value !== null))->save();

        $this->audit->record($officer, 'grant_template.updated', 'grant_template', $template->id, 'Updated grant template '.$template->name, 'done', [], $request);

        return $template->fresh();
    }

    public function deleteTemplate(GrantTemplate $template, Officer $officer, Request $request): void
    {
        $name = $template->name;
        $template->delete();
        $this->audit->record($officer, 'grant_template.deleted', 'grant_template', null, 'Deleted grant template '.$name, 'done', [], $request);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function createDutyPoint(array $payload, Officer $officer, Request $request): DutyPoint
    {
        $point = DutyPoint::create([
            'id' => 'dp_'.Str::lower((string) Str::ulid()),
            'state_code' => $payload['stateCode'] ?? $officer->state_code,
            'lga_id' => $payload['lgaId'] ?? null,
            'name' => $payload['name'],
            'type' => $payload['type'],
            'address' => $payload['address'] ?? null,
            'latitude' => $payload['latitude'] ?? null,
            'longitude' => $payload['longitude'] ?? null,
            'shift_start' => $payload['shiftStart'] ?? '06:00',
            'shift_end' => $payload['shiftEnd'] ?? '18:00',
            'officers_count' => 0,
            'grants_count' => 0,
            'status' => 'active',
        ]);

        $this->audit->record($officer, 'duty_point.created', 'duty_point', $point->id, 'Created duty point '.$point->name, 'done', [], $request);

        return $point;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function updateDutyPoint(DutyPoint $point, array $payload, Officer $officer, Request $request): DutyPoint
    {
        $point->fill(array_filter([
            'name' => $payload['name'] ?? null,
            'type' => $payload['type'] ?? null,
            'lga_id' => $payload['lgaId'] ?? null,
            'address' => $payload['address'] ?? null,
            'latitude' => $payload['latitude'] ?? null,
            'longitude' => $payload['longitude'] ?? null,
            'shift_start' => $payload['shiftStart'] ?? null,
            'shift_end' => $payload['shiftEnd'] ?? null,
            'status' => $payload['status'] ?? null,
        ], static fn ($value): bool => $value !== null))->save();

        $this->audit->record($officer, 'duty_point.updated', 'duty_point', $point->id, 'Updated duty point '.$point->name, 'done', [], $request);

        return $point->fresh();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function createTemplate(array $payload, Officer $officer, Request $request): GrantTemplate
    {
        $template = GrantTemplate::create([
            'id' => 'grt_'.Str::lower((string) Str::ulid()),
            'name' => $payload['name'],
            'description' => $payload['description'] ?? null,
            'scope' => $payload['scope'],
            'modes' => $payload['modes'] ?? [],
            'duration_hours' => $payload['durationHours'],
            'access_count' => $payload['accessCount'] ?? 1,
            'status' => 'active',
            'created_by' => $officer->id,
        ]);

        $this->audit->record($officer, 'grant_template.created', 'grant_template', $template->id, 'Created grant template '.$template->name, 'done', [], $request);

        return $template;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return list<array<string, mixed>>
     */
    public function dutyPoints(array $filters): array
    {
        return DutyPoint::query()
            ->when($filters['stateCode'] ?? null, fn (Builder $q, string $state) => $q->where('state_code', $state))
            ->when($filters['type'] ?? null, fn (Builder $q, string $type) => $q->where('type', $type))
            ->with(['lga', 'state'])->orderBy('name')->get()->map(fn (DutyPoint $point): array => [
                'dutyPointId' => $point->id,
                'name' => $point->name,
                'type' => $point->type,
                'stateCode' => $point->state_code,
                'stateName' => $point->state?->name,
                'lgaId' => $point->lga_id,
                'lgaName' => $point->lga?->name,
                'address' => $point->address,
                'latitude' => (float) $point->latitude,
                'longitude' => (float) $point->longitude,
                'officers' => $point->officers_count,
                'grants' => $point->grants_count,
                'shift' => $point->shift_start.'–'.$point->shift_end,
                'status' => $point->status,
            ])->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function roster(array $filters): array
    {
        $weekStart = Carbon::parse($filters['weekStart'] ?? now()->startOfWeek()->toDateString())->startOfDay();
        $weekEnd = $weekStart->copy()->addDays(6);
        $points = DutyPoint::query()
            ->when($filters['stateCode'] ?? null, fn (Builder $q, string $state) => $q->where('state_code', $state))
            ->when($filters['type'] ?? null, fn (Builder $q, string $type) => $q->where('type', $type))
            ->with(['lga', 'roster' => fn ($q) => $q->whereBetween('roster_date', [$weekStart->toDateString(), $weekEnd->toDateString()])->with('officer')])
            ->orderBy('name')->get();

        return [
            'weekStart' => $weekStart->toDateString(),
            'weekEnd' => $weekEnd->toDateString(),
            'dutyPoints' => $points->map(fn (DutyPoint $point): array => [
                'dutyPointId' => $point->id,
                'name' => $point->name,
                'type' => $point->type,
                'lgaName' => $point->lga?->name,
                'slots' => $point->roster->map(fn (DutyRoster $slot): array => [
                    'rosterId' => $slot->id,
                    'date' => $slot->roster_date->format('Y-m-d'),
                    'shift' => $slot->shift,
                    'officer' => $slot->officer?->full_name,
                    'status' => $slot->status,
                    'published' => $slot->published,
                ])->values()->all(),
            ])->all(),
            'availableOfficers' => Officer::query()
                ->where('state_code', $filters['stateCode'] ?? null)
                ->where('status', 'on_duty')
                ->with('dutyPoint')
                ->get()->map(fn (Officer $officer): array => [
                    'officerId' => $officer->id,
                    'fullName' => $officer->full_name,
                    'serviceNumber' => $officer->service_number,
                    'unit' => $officer->unit,
                    'dutyPoint' => $officer->dutyPoint?->name,
                ])->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function assignRoster(array $payload, Officer $actor, Request $request): DutyRoster
    {
        $point = DutyPoint::query()->find($payload['dutyPointId']);

        if (! $point) {
            throw NpisApiException::make(404, 'NOT_FOUND', 'Duty point not found.');
        }

        $officer = Officer::query()->find($payload['officerId']);
        if (! $officer) {
            throw NpisApiException::make(404, 'NOT_FOUND', 'Officer not found.');
        }

        $conflict = DutyRoster::query()
            ->where('officer_id', $officer->id)
            ->whereDate('roster_date', $payload['date'])
            ->where('duty_point_id', '!=', $point->id)
            ->where('shift', $payload['shift'])
            ->exists();

        $roster = DutyRoster::query()->updateOrCreate(
            ['duty_point_id' => $point->id, 'roster_date' => $payload['date'], 'shift' => $payload['shift']],
            [
                'id' => 'ros_'.Str::lower((string) Str::ulid()),
                'officer_id' => $officer->id,
                'status' => $conflict ? 'conflict' : 'assigned',
                'created_by' => $actor->id,
            ]
        );

        $this->audit->record($actor, 'roster.assigned', 'duty_roster', $roster->id, 'Assigned '.$officer->full_name.' to '.$point->name, $conflict ? 'warning' : 'done', [], $request);

        return $roster->fresh(['officer', 'dutyPoint']);
    }

    public function publishRoster(string $weekStart, Officer $officer, Request $request): int
    {
        $start = Carbon::parse($weekStart)->startOfDay();
        $count = DutyRoster::query()->whereBetween('roster_date', [$start->toDateString(), $start->copy()->addDays(6)->toDateString()])
            ->update(['published' => true]);

        $this->audit->record($officer, 'roster.published', 'duty_roster', null, 'Published roster for week '.$weekStart, 'done', ['slots' => $count], $request);

        return $count;
    }

    /**
     * @return array<string, mixed>
     */
    public function autoFillRoster(string $weekStart, Officer $officer, Request $request): array
    {
        $start = Carbon::parse($weekStart)->startOfDay();
        $end = $start->copy()->addDays(6);
        $assigned = 0;

        foreach (range(0, 6) as $offset) {
            $date = $start->copy()->addDays($offset)->toDateString();
            $vacancies = DutyRoster::query()->whereDate('roster_date', $date)->where('status', 'vacant')->get();
            $available = Officer::query()->where('status', 'on_duty')->whereDoesntHave('grants', fn (Builder $q) => $q->where('status', 'active')->where('ends_at', '>', now()))->get();

            foreach ($vacancies as $vacancy) {
                $candidate = $available->shift();
                if (! $candidate) {
                    break;
                }
                $vacancy->forceFill(['officer_id' => $candidate->id, 'status' => 'assigned'])->save();
                $assigned++;
            }
        }

        $this->audit->record($officer, 'roster.auto_filled', 'duty_roster', null, 'Auto-filled '.$assigned.' roster slots', 'done', [], $request);

        return ['assigned' => $assigned, 'weekStart' => $start->toDateString(), 'weekEnd' => $end->toDateString()];
    }

    /**
     * @param  list<string>  $modes
     * @return list<array<string, mixed>>
     */
    private function findConflicts(string $officerId, Carbon $starts, Carbon $ends, array $modes): array
    {
        return AccessGrant::query()
            ->where('officer_id', $officerId)
            ->where('status', 'active')
            ->where('starts_at', '<', $ends)
            ->where('ends_at', '>', $starts)
            ->get()
            ->filter(fn (AccessGrant $grant): bool => array_intersect((array) $grant->modes, $modes) !== [])
            ->map(fn (AccessGrant $grant): array => [
                'grantNumber' => $grant->grant_number,
                'scope' => $grant->scope,
                'startsAt' => $grant->starts_at->toIso8601String(),
                'endsAt' => $grant->ends_at->toIso8601String(),
                'modes' => $grant->modes,
            ])->values()->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function officerContract(Officer $officer): array
    {
        $officer->loadMissing(['dutyPoint', 'lga']);
        $activeGrant = $officer->grants()->where('status', 'active')->where('ends_at', '>', now())->latest()->first();

        return [
            'officerId' => $officer->id,
            'serviceNumber' => $officer->service_number,
            'rank' => $officer->rank,
            'fullName' => $officer->full_name,
            'unit' => $officer->unit,
            'role' => $officer->role,
            'status' => $officer->status,
            'dutyPoint' => $officer->dutyPoint ? ['dutyPointId' => $officer->dutyPoint->id, 'name' => $officer->dutyPoint->name] : null,
            'lgaName' => $officer->lga?->name,
            'grant' => $activeGrant ? ['grantNumber' => $activeGrant->grant_number, 'status' => $activeGrant->status] : null,
            'grantStatus' => $activeGrant ? $activeGrant->status : 'free',
            'lastActiveAt' => $officer->last_active_at?->toIso8601String(),
            'checksToday' => $officer->checks_today,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function grantContract(AccessGrant $grant): array
    {
        return [
            'grantId' => $grant->id,
            'grantNumber' => $grant->grant_number,
            'officer' => $grant->officer ? ['officerId' => $grant->officer->id, 'fullName' => $grant->officer->full_name, 'serviceNumber' => $grant->officer->service_number] : null,
            'template' => $grant->template ? ['templateId' => $grant->template->id, 'name' => $grant->template->name] : null,
            'scope' => $grant->scope,
            'modes' => $grant->modes,
            'startsAt' => $grant->starts_at->toIso8601String(),
            'endsAt' => $grant->ends_at->toIso8601String(),
            'status' => $grant->status,
            'revokedAt' => $grant->revoked_at?->toIso8601String(),
            'revokeReason' => $grant->revoke_reason,
            'notes' => $grant->notes,
        ];
    }

    private function nextGrantNumber(): string
    {
        $sequence = AccessGrant::query()->count() + 1;

        do {
            $number = 'GR-26-'.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
            $sequence++;
        } while (AccessGrant::query()->where('grant_number', $number)->exists());

        return $number;
    }

    private function duration(int $seconds): string
    {
        return intdiv($seconds, 60).'m '.($seconds % 60).'s';
    }

    /**
     * @return array<string, int>
     */
    private function pagination(int $page, int $pageSize, int $total): array
    {
        return ['page' => $page, 'pageSize' => $pageSize, 'totalItems' => $total, 'totalPages' => max(1, (int) ceil($total / $pageSize))];
    }
}
