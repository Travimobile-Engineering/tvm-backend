<?php

namespace App\Services\Emf\Field;

use App\Models\Emf\FieldCheck;
use App\Models\Emf\FieldCheckSeat;
use App\Models\Emf\FieldManifestEvent;
use App\Models\Emf\Manifest;
use App\Models\Emf\ManifestPassenger;
use Illuminate\Support\Str;

class EmfFieldPresenter
{
    /**
     * @return array<string, mixed>
     */
    public function manifest(Manifest $manifest, bool $includeTracking = false): array
    {
        $manifest->loadMissing(['dutyPoint', 'passengers', 'trackingEvents']);

        $passengers = $manifest->passengers;
        $clearedAtBooking = $passengers->whereIn('verdict', ['clear'])->count();
        $flaggedAtBooking = $passengers->whereIn('verdict', ['flagged', 'mismatch', 'unverified'])->count();
        $arrivalLabel = match ($manifest->arrival_status) {
            'in_transit' => 'In transit',
            'arrived' => 'At checkpoint',
            'checked' => 'Checked',
            default => 'Not arrived yet',
        };

        $data = [
            'manifestId' => $manifest->id,
            'manifestNumber' => $manifest->manifest_number,
            'mode' => $manifest->mode,
            'operator' => $manifest->operator,
            'vehicleRegistration' => $manifest->vehicle_registration,
            'driverName' => $manifest->driver_name,
            'route' => [
                'origin' => $manifest->route_origin,
                'destination' => $manifest->route_destination,
            ],
            'seats' => $manifest->seats,
            'passengerCount' => $manifest->passenger_count,
            'checkedCount' => $manifest->checked_count,
            'verdict' => $manifest->verdict,
            'arrivalStatus' => $manifest->arrival_status,
            'arrivalStatusLabel' => $arrivalLabel,
            'departedAt' => $manifest->departed_at?->toIso8601String(),
            'expectedArrivalAt' => $manifest->expected_arrival_at?->toIso8601String(),
            'checkedAt' => $manifest->checked_at?->toIso8601String(),
            'clearanceReference' => $manifest->clearance_reference,
            'dutyPoint' => $manifest->dutyPoint ? [
                'dutyPointId' => $manifest->dutyPoint->id,
                'name' => $manifest->dutyPoint->name,
            ] : null,
            'counts' => [
                'listed' => $manifest->passenger_count ?: $passengers->count(),
                'clearedAtBooking' => $clearedAtBooking,
                'flaggedAtBooking' => $flaggedAtBooking,
            ],
        ];

        if ($includeTracking) {
            $events = $manifest->trackingEvents->sortBy('logged_at')->values();
            $last = $events->last();
            $data['tracking'] = [
                'lastEvent' => $last instanceof FieldManifestEvent ? [
                    'eventType' => $last->event_type,
                    'location' => $last->location,
                    'loggedAt' => $last->logged_at->toIso8601String(),
                    'source' => $last->source,
                    'note' => $last->note,
                ] : null,
                'events' => $events->map(fn ($event): array => [
                    'eventType' => $event->event_type,
                    'location' => $event->location,
                    'loggedAt' => $event->logged_at->toIso8601String(),
                    'source' => $event->source,
                    'note' => $event->note,
                ])->all(),
            ];
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    public function check(FieldCheck $check): array
    {
        $check->loadMissing(['manifest', 'officer', 'seats', 'incident']);
        $counts = $this->counts($check);

        return [
            'checkId' => $check->id,
            'checkReference' => $check->check_reference,
            'status' => $check->status,
            'startedAt' => $check->started_at->toIso8601String(),
            'completedAt' => $check->completed_at?->toIso8601String(),
            'clearanceReference' => $check->clearance_reference,
            'vehicleCleared' => $check->vehicle_cleared,
            'hold' => [
                'held' => $check->hold_at !== null,
                'heldAt' => $check->hold_at?->toIso8601String(),
                'heldForSeconds' => $check->hold_at ? max(0, (int) $check->hold_at->diffInSeconds(now())) : 0,
                'reason' => $check->hold_reason,
                'opsRoomNotifiedAt' => $check->hold_at?->toIso8601String(),
                'acknowledgedAt' => $check->hold_acknowledged_at?->toIso8601String(),
                'assignedOfficer' => $check->incident?->responder?->full_name,
                'opsAlertReference' => $check->ops_alert_reference,
                'releasedAt' => $check->released_at?->toIso8601String(),
                'passengersWaiting' => max(0, $counts['listed'] - $counts['present'] - $counts['clearedWithNote']),
            ],
            'counts' => $counts,
            'progress' => $this->progress($counts),
            'manifest' => $check->manifest ? $this->manifest($check->manifest, true) : null,
            'officer' => $check->officer ? [
                'officerId' => $check->officer->id,
                'fullName' => $check->officer->full_name,
                'serviceNumber' => $check->officer->service_number,
                'rank' => $check->officer->rank,
            ] : null,
            'incident' => $check->incident ? [
                'incidentId' => $check->incident->id,
                'incidentNumber' => $check->incident->incident_number,
                'status' => $check->incident->status,
                'reportState' => $check->incident->report_state,
            ] : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function workspace(FieldCheck $check): array
    {
        $check->loadMissing([
            'manifest.passengers',
            'manifest.dutyPoint',
            'officer',
            'seats.passenger',
            'seats.alert',
            'seats.incident',
            'incident',
        ]);

        $seatData = $check->seats->sortBy(fn (FieldCheckSeat $seat): int => (int) ($seat->seat ?? 0))
            ->values()->map(fn (FieldCheckSeat $seat): array => $this->seat($seat))->all();
        $counts = $this->counts($check);
        $progress = $this->progress($counts);
        $nextSeat = collect($seatData)->firstWhere('status', 'pending');

        return [
            ...$this->check($check),
            'seats' => $seatData,
            'readyToClear' => $progress['readyToClear'],
            'nextSeatId' => is_array($nextSeat) ? ($nextSeat['passengerId'] ?? null) : null,
            'actions' => [
                'confirmSeat',
                'flagSeat',
                'resolveSeat',
                'escalateSeat',
                'holdBus',
                'acknowledgeHold',
                'releaseBus',
                'clearVehicle',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function record(FieldCheck $check): array
    {
        $workspace = $this->workspace($check);
        $timeline = [];

        $timeline[] = [
            'type' => 'check_started',
            'label' => 'Check started',
            'at' => $check->started_at->toIso8601String(),
            'detail' => 'Manifest check started by '.($check->officer->full_name ?? 'officer'),
        ];

        foreach ($check->seats as $seat) {
            if ($seat->confirmed_at) {
                $timeline[] = [
                    'type' => 'seat_confirmed',
                    'label' => 'Seat '.$seat->seat.' confirmed',
                    'at' => $seat->confirmed_at->toIso8601String(),
                    'detail' => $seat->passenger?->full_name,
                ];
            }
            if ($seat->flagged_at) {
                $timeline[] = [
                    'type' => 'seat_flagged',
                    'label' => 'Seat '.$seat->seat.' flagged',
                    'at' => $seat->flagged_at->toIso8601String(),
                    'detail' => $seat->reason ?? 'Passenger flagged',
                ];
            }
            if ($seat->resolved_at) {
                $timeline[] = [
                    'type' => 'seat_resolved',
                    'label' => 'Seat '.$seat->seat.' resolved',
                    'at' => $seat->resolved_at->toIso8601String(),
                    'detail' => $seat->note,
                ];
            }
        }

        if ($check->hold_at) {
            $timeline[] = [
                'type' => 'bus_held',
                'label' => 'Bus held for Ops room',
                'at' => $check->hold_at->toIso8601String(),
                'detail' => $check->hold_reason,
            ];
        }
        if ($check->released_at) {
            $timeline[] = [
                'type' => 'bus_released',
                'label' => 'Ops room took over — bus released',
                'at' => $check->released_at->toIso8601String(),
                'detail' => $check->incident?->incident_number,
            ];
        }
        if ($check->clearance_reference) {
            $timeline[] = [
                'type' => 'vehicle_cleared',
                'label' => 'Vehicle cleared',
                'at' => $check->completed_at?->toIso8601String(),
                'detail' => $check->clearance_reference,
            ];
        }

        usort($timeline, static fn (array $a, array $b): int => strcmp((string) $a['at'], (string) $b['at']));

        return [
            ...$workspace,
            'locked' => $check->completed_at !== null,
            'timeline' => $timeline,
            'incidentReport' => $check->incident ? [
                'incidentId' => $check->incident->id,
                'incidentNumber' => $check->incident->incident_number,
                'status' => $check->incident->status,
            ] : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function seat(FieldCheckSeat $seat): array
    {
        $passenger = $seat->passenger;
        $record = $passenger->nin_record ?? [];

        return [
            'seatId' => $seat->id,
            'passengerId' => $seat->manifest_passenger_id,
            'seat' => $seat->seat,
            'fullName' => $passenger?->full_name,
            'initials' => $passenger ? $this->initials($passenger->full_name) : null,
            'status' => $seat->status,
            'reason' => $seat->reason,
            'note' => $seat->note,
            'confirmedAt' => $seat->confirmed_at?->toIso8601String(),
            'flaggedAt' => $seat->flagged_at?->toIso8601String(),
            'resolvedAt' => $seat->resolved_at?->toIso8601String(),
            'alertId' => $seat->alert_id,
            'incidentId' => $seat->incident_id,
            'identity' => [
                'photoUrl' => $passenger?->photo_url,
                'age' => $passenger?->dob?->age,
                'sex' => $passenger?->sex,
                'ninMasked' => $this->mask($passenger?->nin),
                'phoneMasked' => $this->mask($passenger?->phone),
                'confidence' => $passenger?->identity_confidence,
                'verifiedAt' => $passenger?->checked_at?->toIso8601String(),
                'manifestRecord' => [
                    'name' => $passenger?->full_name,
                    'dob' => $passenger?->dob?->format('Y-m-d'),
                    'sex' => $passenger?->sex,
                    'phone' => $passenger?->phone,
                ],
                'ninRecord' => [
                    'name' => $record['name'] ?? $passenger?->full_name,
                    'dob' => $record['dob'] ?? $passenger?->dob?->format('Y-m-d'),
                    'sex' => $record['sex'] ?? $passenger?->sex,
                    'phone' => $record['phone'] ?? $passenger?->phone,
                    'photoUrl' => $record['photoUrl'] ?? $passenger?->photo_url,
                ],
                'matches' => [
                    'name' => $this->namesMatch($passenger),
                    'dob' => ($record['dob'] ?? $passenger?->dob?->format('Y-m-d')) === $passenger?->dob?->format('Y-m-d'),
                    'sex' => ($record['sex'] ?? $passenger?->sex) === $passenger?->sex,
                    'phone' => ($record['phone'] ?? $passenger?->phone) === $passenger?->phone,
                ],
            ],
            'actions' => $this->seatActions($seat),
        ];
    }

    /**
     * @param  list<string>  $fields
     * @return array<string, mixed>
     */
    public function reveal(FieldCheckSeat $seat, array $fields): array
    {
        $passenger = $seat->passenger;
        $data = [];

        if (in_array('nin', $fields, true)) {
            $data['nin'] = $passenger?->nin;
        }
        if (in_array('phone', $fields, true)) {
            $data['phone'] = $passenger?->phone;
        }

        return $data;
    }

    /**
     * @return array<string, int>
     */
    public function counts(FieldCheck $check): array
    {
        $seats = $check->seats;

        return [
            'listed' => $check->listed_count ?: $seats->count(),
            'present' => $seats->where('status', 'present')->count(),
            'flagged' => $seats->where('status', 'flagged')->count(),
            'mismatch' => $seats->where('status', 'mismatch')->count(),
            'unverified' => $seats->where('status', 'unverified')->count(),
            'escalated' => $seats->where('status', 'escalated')->count(),
            'clearedWithNote' => $seats->where('status', 'cleared_with_note')->count(),
            'pending' => $seats->where('status', 'pending')->count(),
        ];
    }

    /**
     * @param  array<string, int>  $counts
     * @return array<string, int|float|bool>
     */
    public function progress(array $counts): array
    {
        $done = $counts['present'] + $counts['clearedWithNote'];
        $remaining = max(0, $counts['listed'] - $done);
        $blocked = $counts['flagged'] + $counts['mismatch'] + $counts['unverified'] + $counts['escalated'];

        return [
            'total' => $counts['listed'],
            'done' => $done,
            'remaining' => $remaining,
            'blocked' => $blocked,
            'percent' => $counts['listed'] > 0 ? (int) round(($done / $counts['listed']) * 100) : 0,
            'readyToClear' => $counts['listed'] > 0 && $remaining === 0 && $blocked === 0,
        ];
    }

    /**
     * @return list<string>
     */
    private function seatActions(FieldCheckSeat $seat): array
    {
        return match ($seat->status) {
            'pending' => ['confirm', 'flag', 'escalate'],
            'flagged', 'mismatch', 'unverified' => ['resolve', 'escalate', 'reveal'],
            'escalated' => ['reveal'],
            default => ['reveal'],
        };
    }

    private function namesMatch(?ManifestPassenger $passenger): bool
    {
        if (! $passenger instanceof ManifestPassenger) {
            return false;
        }

        $recordName = $passenger->nin_record['name'] ?? $passenger->full_name;

        return $this->normalizeName((string) $recordName) === $this->normalizeName($passenger->full_name);
    }

    private function normalizeName(string $name): string
    {
        return Str::lower(preg_replace('/[^a-z0-9]+/i', '', $name) ?? '');
    }

    private function initials(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];

        return Str::upper(implode('', array_map(static fn (string $part): string => Str::substr($part, 0, 1), array_slice($parts, 0, 2))));
    }

    private function mask(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $visible = Str::substr($value, -4);

        return str_repeat('•', max(0, strlen($value) - 4)).$visible;
    }
}
