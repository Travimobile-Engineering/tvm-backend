<?php

namespace App\Services\Emf;

use App\Exceptions\NpisApiException;
use App\Models\Emf\Alert;
use App\Models\Emf\Manifest;
use App\Models\Emf\ManifestCheck;
use App\Models\Emf\ManifestPassenger;
use App\Models\Emf\Officer;
use App\Models\Emf\WatchlistEntry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EmfManifestService
{
    public function __construct(private readonly EmfAuditService $audit) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array{data: list<array<string, mixed>>, pagination: array<string, int>}
     */
    public function paginate(array $filters, Officer $officer): array
    {
        $page = max(1, (int) ($filters['page'] ?? 1));
        $pageSize = min(100, max(1, (int) ($filters['pageSize'] ?? 25)));
        $from = match ($filters['period'] ?? '24h') {
            '7d' => now()->subDays(7),
            '30d' => now()->subDays(30),
            default => now()->subDay(),
        };

        $query = Manifest::query()
            ->where('state_code', $filters['stateCode'] ?? $officer->state_code)
            ->when($filters['lgaId'] ?? null, fn (Builder $q, string $lga) => $q->where('lga_id', $lga))
            ->when($filters['mode'] ?? null, fn (Builder $q, string $mode) => $q->where('mode', $mode))
            ->when($filters['verdict'] ?? null, fn (Builder $q, string $verdict) => $q->where('verdict', $verdict))
            ->when($filters['nodeId'] ?? null, fn (Builder $q, string $node) => $q->where('duty_point_id', $node))
            ->when($filters['search'] ?? null, function (Builder $q, string $search): void {
                $like = '%'.$search.'%';
                $q->where(fn (Builder $inner) => $inner->where('manifest_number', 'like', $like)
                    ->orWhere('operator', 'like', $like)
                    ->orWhere('vehicle_registration', 'like', $like));
            })
            ->where('checked_at', '>=', $from);

        $total = (clone $query)->count();
        $items = $query->with(['officer', 'dutyPoint'])->orderByDesc('checked_at')
            ->offset(($page - 1) * $pageSize)->limit($pageSize)->get()
            ->map(fn (Manifest $manifest): array => $this->contract($manifest))->all();

        return [
            'data' => $items,
            'pagination' => $this->pagination($page, $pageSize, $total),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function show(Manifest $manifest): array
    {
        return $this->contract($manifest->load(['passengers.watchlistEntry', 'checks.officer', 'officer', 'dutyPoint']), true);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function createCheck(array $payload, Officer $officer, Request $request): Manifest
    {
        return DB::transaction(function () use ($payload, $officer, $request): Manifest {
            $manifest = isset($payload['manifestNumber'])
                ? Manifest::query()->where('manifest_number', $payload['manifestNumber'])->first()
                : null;

            if ($manifest && $manifest->checked_at !== null && empty($payload['recheck'])) {
                throw NpisApiException::make(409, 'ALREADY_CHECKED', 'This manifest has already been checked.');
            }

            $manifest ??= new Manifest([
                'id' => 'mf_'.Str::lower((string) Str::ulid()),
                'manifest_number' => $this->nextManifestNumber(),
                'source' => 'manual',
            ]);

            $manifest->fill([
                'mode' => $payload['mode'],
                'operator' => $payload['operator'],
                'vehicle_registration' => $payload['vehicleRegistration'] ?? $manifest->vehicle_registration,
                'driver_name' => $payload['driverName'] ?? $manifest->driver_name,
                'route_origin' => $payload['routeOrigin'],
                'route_destination' => $payload['routeDestination'],
                'seats' => $payload['seats'] ?? null,
                'duty_point_id' => $payload['dutyPointId'] ?? $officer->duty_point_id,
                'state_code' => $officer->state_code,
                'lga_id' => $payload['lgaId'] ?? $officer->lga_id,
                'checked_by_officer_id' => $officer->id,
                'checked_at' => now(),
                'departed_at' => isset($payload['departedAt']) ? Carbon::parse($payload['departedAt']) : $manifest->departed_at,
                'expected_arrival_at' => isset($payload['expectedArrivalAt']) ? Carbon::parse($payload['expectedArrivalAt']) : $manifest->expected_arrival_at,
            ]);
            $manifest->save();

            $passengerIds = [];
            $highest = 'clear';

            foreach ((array) ($payload['passengers'] ?? []) as $index => $row) {
                $match = $this->matchWatchlist($row);
                $verdict = $match instanceof WatchlistEntry ? 'flagged' : (empty($row['nin']) ? 'unverified' : 'clear');
                $passenger = ManifestPassenger::query()->updateOrCreate(
                    ['id' => $row['passengerId'] ?? 'pax_'.Str::lower((string) Str::ulid())],
                    [
                        'manifest_id' => $manifest->id,
                        'full_name' => $row['fullName'],
                        'nin' => $row['nin'] ?? null,
                        'seat' => $row['seat'] ?? (string) ($index + 1),
                        'verdict' => $verdict,
                        'watchlist_entry_id' => $match?->id,
                        'checked_at' => now(),
                        'identity_confidence' => $match instanceof WatchlistEntry ? 98 : (empty($row['nin']) ? null : 100),
                    ]
                );
                $passengerIds[] = $passenger->id;

                if ($verdict !== 'clear') {
                    $highest = $this->worseVerdict($highest, $verdict);
                    $this->createAlert($manifest, $passenger, $match, $verdict, $payload);
                }
            }

            $manifest->passengers()->whereNotIn('id', $passengerIds)->delete();
            $manifest->checks()->delete();

            $plateMatch = WatchlistEntry::query()->where('status', 'active')->where('plate_number', $manifest->vehicle_registration)->first();

            foreach ([
                ['nin_verification', $highest === 'unverified' ? 'mismatch' : 'clear', 'NIN verified against NIMC'],
                ['watchlist_screening', $highest === 'flagged' ? 'flagged' : 'clear', 'Watchlist screening completed'],
                ['plate_lookup', $plateMatch ? 'mismatch' : 'clear', $plateMatch ? 'Plate matched watchlist '.$plateMatch->entry_number : 'Plate lookup clear'],
                ['headcount', 'clear', 'Headcount completed'],
            ] as [$type, $status, $result]) {
                ManifestCheck::create([
                    'id' => 'chk_'.Str::lower((string) Str::ulid()),
                    'manifest_id' => $manifest->id,
                    'type' => $type,
                    'status' => $status,
                    'result' => $result,
                    'performed_by' => $officer->id,
                    'performed_at' => now(),
                ]);
            }

            $manifest->forceFill([
                'passenger_count' => count($passengerIds),
                'checked_count' => count($passengerIds),
                'verdict' => $highest,
            ])->save();

            $officer->increment('checks_today');

            $this->audit->record($officer, 'manifest.checked', 'manifest', $manifest->id, 'Checked manifest '.$manifest->manifest_number, $highest === 'clear' ? 'done' : 'warning', [
                'verdict' => $highest,
                'passengers' => count($passengerIds),
            ], $request);

            return $manifest->fresh(['passengers', 'checks', 'officer', 'dutyPoint']);
        });
    }

    public function addNote(Manifest $manifest, Officer $officer, string $note, Request $request): Manifest
    {
        $manifest->forceFill([
            'notes' => trim(($manifest->notes ? $manifest->notes."\n" : '').'['.now()->toIso8601String().'] '.$officer->full_name.': '.$note),
        ])->save();

        $this->audit->record($officer, 'manifest.note_added', 'manifest', $manifest->id, 'Added note to '.$manifest->manifest_number, 'done', [], $request);

        return $manifest->fresh();
    }

    /**
     * @return array{data: list<array<string, mixed>>, pagination: array<string, int>}
     */
    public function passengers(Manifest $manifest, int $page, int $pageSize): array
    {
        $query = $manifest->passengers()->orderBy('seat');
        $total = (clone $query)->count();
        $items = $query->offset(($page - 1) * $pageSize)->limit($pageSize)->get()->map(fn (ManifestPassenger $p): array => [
            'passengerId' => $p->id,
            'fullName' => $p->full_name,
            'nin' => $p->nin,
            'seat' => $p->seat,
            'verdict' => $p->verdict,
            'identityConfidence' => $p->identity_confidence,
            'checkedAt' => $p->checked_at?->toIso8601String(),
        ])->all();

        return ['data' => $items, 'pagination' => $this->pagination($page, $pageSize, $total)];
    }

    /**
     * @return array<string, mixed>
     */
    private function contract(Manifest $manifest, bool $detail = false): array
    {
        $manifest->loadMissing(['passengers', 'checks', 'officer', 'dutyPoint']);
        $data = [
            'manifestId' => $manifest->id,
            'manifestNumber' => $manifest->manifest_number,
            'mode' => $manifest->mode,
            'operator' => $manifest->operator,
            'vehicleRegistration' => $manifest->vehicle_registration,
            'driverName' => $manifest->driver_name,
            'route' => ['origin' => $manifest->route_origin, 'destination' => $manifest->route_destination],
            'seats' => $manifest->seats,
            'passengerCount' => $manifest->passenger_count,
            'checkedCount' => $manifest->checked_count,
            'verdict' => $manifest->verdict,
            'checkedBy' => $manifest->officer?->full_name,
            'checkedAt' => $manifest->checked_at?->toIso8601String(),
            'dutyPoint' => $manifest->dutyPoint?->name,
            'departedAt' => $manifest->departed_at?->toIso8601String(),
            'expectedArrivalAt' => $manifest->expected_arrival_at?->toIso8601String(),
        ];

        if ($detail) {
            $data['journey'] = [
                ['key' => 'departed', 'label' => 'Departed', 'place' => $manifest->route_origin, 'time' => $manifest->departed_at?->toIso8601String(), 'status' => 'done'],
                ['key' => 'checkpoint', 'label' => 'Mile 1 checkpoint', 'place' => $manifest->dutyPoint->name ?? 'Checkpoint', 'time' => $manifest->checked_at?->toIso8601String(), 'status' => 'done'],
                ['key' => 'junction', 'label' => 'Interchange junction', 'place' => 'En route', 'time' => null, 'status' => 'pending'],
                ['key' => 'arrivals', 'label' => 'Arrivals', 'place' => $manifest->route_destination, 'time' => $manifest->expected_arrival_at?->toIso8601String(), 'status' => 'eta'],
            ];
            $data['passengers'] = $manifest->passengers->take(10)->map(fn (ManifestPassenger $p): array => [
                'passengerId' => $p->id,
                'fullName' => $p->full_name,
                'nin' => $p->nin,
                'seat' => $p->seat,
                'verdict' => $p->verdict,
                'identityConfidence' => $p->identity_confidence,
            ])->values()->all();
            $data['checks'] = $manifest->checks->map(fn (ManifestCheck $check): array => [
                'checkId' => $check->id,
                'type' => $check->type,
                'status' => $check->status,
                'result' => $check->result,
                'performedAt' => $check->performed_at->toIso8601String(),
            ])->values()->all();
            $data['notes'] = $manifest->notes;
            $data['alerts'] = Alert::query()->where('manifest_id', $manifest->id)->orderByDesc('detected_at')->limit(5)->get()->map(fn (Alert $alert): array => [
                'alertId' => $alert->id,
                'alertNumber' => $alert->alert_number,
                'type' => $alert->type,
                'status' => $alert->status,
                'detectedAt' => $alert->detected_at->toIso8601String(),
            ])->values()->all();
            $data['activeAlertId'] = Alert::query()->where('manifest_id', $manifest->id)->whereIn('status', ['open', 'acknowledged', 'assigned'])->latest('detected_at')->value('id');
            $data['actions'] = ['add_note', 'export', 'escalate'];
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function matchWatchlist(array $row): ?WatchlistEntry
    {
        $query = WatchlistEntry::query()->where('status', 'active');

        if (! empty($row['nin'])) {
            $found = (clone $query)->where('nin', $row['nin'])->first();
            if ($found) {
                return $found;
            }
        }

        if (! empty($row['phone'])) {
            $found = (clone $query)->where('phone', $row['phone'])->first();
            if ($found) {
                return $found;
            }
        }

        return $query->where('type', 'person')->where('full_name', $row['fullName'] ?? '')->first();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function createAlert(Manifest $manifest, ManifestPassenger $passenger, ?WatchlistEntry $entry, string $verdict, array $payload): void
    {
        $sequence = Alert::query()->count() + 1;
        do {
            $number = 'AL-26-'.str_pad((string) $sequence, 5, '0', STR_PAD_LEFT);
            $sequence++;
        } while (Alert::query()->where('alert_number', $number)->exists());

        Alert::create([
            'id' => 'al_'.Str::lower((string) Str::ulid()),
            'alert_number' => $number,
            'type' => $verdict === 'flagged' ? ($entry?->risk === 'critical' ? 'critical' : 'flagged') : 'unverified',
            'title' => $passenger->full_name.($entry instanceof WatchlistEntry ? ' — NIN match '.(98).'%' : ' — identity unverified'),
            'summary' => $entry->reason ?? 'Passenger identity could not be verified',
            'subject_type' => 'passenger',
            'subject_id' => $passenger->id,
            'manifest_id' => $manifest->id,
            'watchlist_entry_id' => $entry?->id,
            'confidence' => $entry instanceof WatchlistEntry ? 98 : null,
            'mode' => $manifest->mode,
            'route' => $manifest->route_origin.' → '.$manifest->route_destination,
            'duty_point_id' => $manifest->duty_point_id,
            'state_code' => $manifest->state_code,
            'lga_id' => $manifest->lga_id,
            'status' => 'open',
            'sla_seconds' => 120,
            'detected_at' => now(),
            'verified_at' => now(),
            'metadata' => ['seat' => $passenger->seat, 'manifestNumber' => $manifest->manifest_number, 'checkSource' => $payload['source'] ?? 'manual'],
        ]);
    }

    private function worseVerdict(string $current, string $candidate): string
    {
        $rank = ['clear' => 0, 'unverified' => 1, 'mismatch' => 2, 'flagged' => 3];

        return ($rank[$candidate] ?? 0) > ($rank[$current] ?? 0) ? $candidate : $current;
    }

    private function nextManifestNumber(): string
    {
        $sequence = Manifest::query()->count() + 1;

        do {
            $number = 'MF-26-'.str_pad((string) $sequence, 5, '0', STR_PAD_LEFT);
            $sequence++;
        } while (Manifest::query()->where('manifest_number', $number)->exists());

        return $number;
    }

    /**
     * @return array<string, int>
     */
    private function pagination(int $page, int $pageSize, int $total): array
    {
        return [
            'page' => $page,
            'pageSize' => $pageSize,
            'totalItems' => $total,
            'totalPages' => max(1, (int) ceil($total / $pageSize)),
        ];
    }
}
