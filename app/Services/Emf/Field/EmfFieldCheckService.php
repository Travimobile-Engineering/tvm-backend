<?php

namespace App\Services\Emf\Field;

use App\Exceptions\NpisApiException;
use App\Models\Emf\Alert;
use App\Models\Emf\AlertAction;
use App\Models\Emf\FieldCheck;
use App\Models\Emf\FieldCheckSeat;
use App\Models\Emf\FieldManifestEvent;
use App\Models\Emf\Incident;
use App\Models\Emf\Manifest;
use App\Models\Emf\ManifestPassenger;
use App\Models\Emf\Officer;
use App\Services\Emf\EmfAuditService;
use App\Services\Emf\EmfIncidentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EmfFieldCheckService
{
    public function __construct(
        private readonly EmfFieldPresenter $presenter,
        private readonly EmfAuditService $audit,
        private readonly EmfIncidentService $incidents,
    ) {}

    public function start(Manifest $manifest, Officer $officer, Request $request): FieldCheck
    {
        return DB::transaction(function () use ($manifest, $officer, $request): FieldCheck {
            $existing = FieldCheck::query()
                ->where('manifest_id', $manifest->id)
                ->whereIn('status', ['in_progress', 'ready', 'on_hold'])
                ->latest('started_at')
                ->first();

            if ($existing) {
                if ($existing->officer_id !== $officer->id) {
                    throw NpisApiException::make(409, 'CHECK_IN_PROGRESS', 'Another officer is already checking this manifest.');
                }

                return $existing->load(['manifest.passengers', 'seats.passenger', 'officer', 'incident']);
            }

            $check = FieldCheck::create([
                'id' => 'fchk_'.Str::lower((string) Str::ulid()),
                'check_reference' => $this->reference('CHK'),
                'manifest_id' => $manifest->id,
                'officer_id' => $officer->id,
                'duty_point_id' => $manifest->duty_point_id ?? $officer->duty_point_id,
                'status' => 'in_progress',
                'started_at' => now(),
                'listed_count' => $manifest->passenger_count ?: $manifest->passengers()->count(),
                'metadata' => ['source' => 'field_app'],
            ]);

            foreach ($manifest->passengers as $passenger) {
                $status = match ($passenger->verdict) {
                    'flagged' => 'flagged',
                    'mismatch' => 'mismatch',
                    'unverified' => 'unverified',
                    default => 'pending',
                };

                FieldCheckSeat::create([
                    'id' => 'fseat_'.Str::lower((string) Str::ulid()),
                    'field_check_id' => $check->id,
                    'manifest_passenger_id' => $passenger->id,
                    'seat' => $passenger->seat,
                    'status' => $status,
                    'reason' => $status !== 'pending' ? 'flagged_at_booking' : null,
                    'identity_snapshot' => [
                        'name' => $passenger->full_name,
                        'nin' => $passenger->nin,
                        'dob' => $passenger->dob?->format('Y-m-d'),
                        'sex' => $passenger->sex,
                        'phone' => $passenger->phone,
                        'photoUrl' => $passenger->photo_url,
                        'confidence' => $passenger->identity_confidence,
                    ],
                ]);
            }

            $manifest->forceFill(['arrival_status' => 'arrived'])->save();

            FieldManifestEvent::create([
                'manifest_id' => $manifest->id,
                'event_type' => 'check_started',
                'location' => $manifest->dutyPoint->name ?? $officer->dutyPoint->name ?? 'Checkpoint',
                'logged_at' => now(),
                'source' => 'manual',
                'note' => 'Field manifest check started.',
                'actor_id' => $officer->id,
            ]);

            $this->refreshCounts($check);
            $this->audit->record($officer, 'field.check_started', 'field_check', $check->id, 'Started check '.$check->check_reference.' for '.$manifest->manifest_number, 'done', [], $request);

            return $check->fresh(['manifest.passengers', 'seats.passenger', 'officer', 'incident']);
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function show(FieldCheck $check): array
    {
        return $this->presenter->workspace($check);
    }

    /**
     * @return array<string, mixed>
     */
    public function record(FieldCheck $check): array
    {
        return $this->presenter->record($check);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function confirm(FieldCheck $check, ManifestPassenger $passenger, array $payload, Officer $officer, Request $request): array
    {
        $seat = $this->seat($check, $passenger);
        $hadIssue = in_array($seat->status, ['mismatch', 'unverified', 'flagged'], true);
        $note = $payload['note'] ?? null;
        $status = $hadIssue || $note ? 'cleared_with_note' : 'present';

        $seat->forceFill([
            'status' => $status,
            'note' => $note ?? $seat->note,
            'confirmed_at' => now(),
            'reason' => null,
        ])->save();

        $passenger->forceFill(['verdict' => 'clear', 'checked_at' => now()])->save();
        $this->closeSeatAlert($seat, 'cleared', 'Passenger confirmed at the checkpoint.');
        $this->refreshCounts($check);
        $this->audit->record($officer, 'field.seat_confirmed', 'field_check', $check->id, 'Confirmed seat '.$passenger->seat, 'done', [], $request);

        return [
            'message' => 'Seat '.$passenger->seat.' confirmed.',
            'seat' => $this->presenter->seat($seat->fresh()),
            'check' => $this->presenter->workspace($check->fresh(['seats.passenger', 'manifest', 'officer', 'incident'])),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function flag(FieldCheck $check, ManifestPassenger $passenger, array $payload, Officer $officer, Request $request): array
    {
        $seat = $this->seat($check, $passenger);
        $reason = (string) $payload['reason'];
        $status = match ($reason) {
            'name_mismatch' => 'mismatch',
            'unverified_id' => 'unverified',
            default => 'flagged',
        };
        $passenger->forceFill(['verdict' => $status])->save();

        $alert = $this->createSeatAlert($check, $passenger, $seat, $reason, (string) ($payload['note'] ?? ''), $status);
        $seat->forceFill([
            'status' => $status,
            'reason' => $reason,
            'note' => $payload['note'] ?? null,
            'flagged_at' => now(),
            'alert_id' => $alert->id,
        ])->save();
        $this->refreshCounts($check);
        $this->audit->record($officer, 'field.seat_flagged', 'field_check', $check->id, 'Flagged seat '.$passenger->seat.' — '.$reason, 'warning', ['alertId' => $alert->id], $request);

        return [
            'message' => 'Seat '.$passenger->seat.' flagged successfully.',
            'reason' => $reason,
            'alertId' => $alert->id,
            'alertNumber' => $alert->alert_number,
            'seat' => $this->presenter->seat($seat->fresh()),
            'check' => $this->presenter->workspace($check->fresh(['seats.passenger', 'manifest', 'officer', 'incident'])),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function resolve(FieldCheck $check, ManifestPassenger $passenger, array $payload, Officer $officer, Request $request): array
    {
        $seat = $this->seat($check, $passenger);

        if (! in_array($seat->status, ['flagged', 'mismatch', 'unverified', 'escalated'], true)) {
            throw NpisApiException::make(409, 'INVALID_STATE', 'This seat is not waiting for a resolution.');
        }

        $seat->forceFill([
            'status' => 'cleared_with_note',
            'note' => $payload['note'] ?? 'Resolved by field officer.',
            'resolved_at' => now(),
            'resolved_by' => $officer->id,
        ])->save();
        $passenger->forceFill(['verdict' => 'clear', 'checked_at' => now()])->save();
        $this->closeSeatAlert($seat, 'cleared', (string) ($payload['note'] ?? 'Resolved by field officer.'));
        $this->refreshCounts($check);
        $this->audit->record($officer, 'field.seat_resolved', 'field_check', $check->id, 'Resolved seat '.$passenger->seat, 'done', [], $request);

        return [
            'message' => 'Seat '.$passenger->seat.' resolved.',
            'seat' => $this->presenter->seat($seat->fresh()),
            'check' => $this->presenter->workspace($check->fresh(['seats.passenger', 'manifest', 'officer', 'incident'])),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function escalate(FieldCheck $check, ManifestPassenger $passenger, array $payload, Officer $officer, Request $request): array
    {
        $seat = $this->seat($check, $passenger);
        $category = (string) $payload['category'];
        $severity = (string) ($payload['severity'] ?? 'high');
        $actionTaken = (string) ($payload['actionTaken'] ?? 'vehicle_detained');
        $reference = $this->escalationReference();

        $incident = $this->incidents->create([
            'title' => 'Priority alert — '.$category,
            'type' => 'field_escalation',
            'severity' => $severity,
            'summary' => $payload['note'] ?? 'Priority alert raised from the field manifest check.',
            'manifestId' => $check->manifest_id,
            'watchlistEntryId' => $passenger->watchlist_entry_id,
            'location' => $check->manifest->dutyPoint->name ?? 'Checkpoint',
            'dutyPointId' => $check->duty_point_id,
            'assigneeId' => $payload['assigneeId'] ?? null,
            'source' => 'field_app',
        ], $officer, $request);

        $incident->forceFill([
            'category' => $category,
            'report_state' => 'escalated',
            'signed_at' => now(),
            'signed_by' => $officer->id,
            'linked_vehicle' => $check->manifest?->vehicle_registration,
            'occurred_at' => now(),
            'metadata' => array_merge($incident->metadata ?? [], [
                'fieldCheckId' => $check->id,
                'seatId' => $seat->id,
                'seat' => $passenger->seat,
                'category' => $category,
                'actionTaken' => $actionTaken,
                'escalationReference' => $reference,
            ]),
        ])->save();

        $alert = $this->createSeatAlert($check, $passenger, $seat, 'priority_escalation', (string) ($payload['note'] ?? ''), 'critical');
        $alert->forceFill([
            'status' => 'escalated',
            'assigned_officer_id' => $payload['assigneeId'] ?? null,
            'match_rule' => 'Field escalation',
            'metadata' => array_merge($alert->metadata ?? [], [
                'incidentId' => $incident->id,
                'category' => $category,
                'actionTaken' => $actionTaken,
                'escalationReference' => $reference,
            ]),
        ])->save();

        AlertAction::create([
            'alert_id' => $alert->id,
            'action' => 'escalated',
            'note' => $payload['note'] ?? 'Priority alert sent to command ops room.',
            'actor_id' => $officer->id,
            'metadata' => ['category' => $category, 'actionTaken' => $actionTaken],
        ]);

        $seat->forceFill([
            'status' => 'escalated',
            'reason' => $category,
            'note' => $payload['note'] ?? null,
            'flagged_at' => $seat->flagged_at ?? now(),
            'alert_id' => $alert->id,
            'incident_id' => $incident->id,
        ])->save();

        $check->forceFill([
            'status' => 'on_hold',
            'hold_at' => now(),
            'hold_reason' => $category,
            'ops_alert_reference' => $reference,
            'incident_id' => $incident->id,
        ])->save();
        $this->refreshCounts($check);
        $this->audit->record($officer, 'field.priority_escalated', 'incident', $incident->id, 'Priority alert '.$reference.' sent to ops room', 'warning', [], $request);

        return [
            'message' => 'Priority alert sent to command ops room.',
            'escalationReference' => $reference,
            'incidentId' => $incident->id,
            'incidentNumber' => $incident->incident_number,
            'opsRoom' => $officer->ops_room ?? 'State command operations room',
            'instructions' => [
                'Keep the bus at the checkpoint.',
                'Keep seat '.$passenger->seat.' in sight. Do not release or question them further.',
                'The ops room will call you within 5 minutes.',
            ],
            'check' => $this->presenter->workspace($check->fresh(['seats.passenger', 'manifest', 'officer', 'incident'])),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function hold(FieldCheck $check, array $payload, Officer $officer, Request $request): array
    {
        $reference = $this->reference('HLD');
        $check->forceFill([
            'status' => 'on_hold',
            'hold_at' => now(),
            'hold_reason' => $payload['reason'] ?? 'Held for ops room review.',
            'ops_alert_reference' => $reference,
        ])->save();

        $this->audit->record($officer, 'field.bus_held', 'field_check', $check->id, 'Bus held for ops room', 'warning', ['reference' => $reference], $request);

        return [
            'message' => 'Bus held. Ops room notified.',
            'holdReference' => $reference,
            'opsRoom' => $officer->ops_room ?? 'State command operations room',
            'check' => $this->presenter->workspace($check->fresh(['seats.passenger', 'manifest', 'officer', 'incident'])),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function acknowledgeHold(FieldCheck $check, Officer $officer, Request $request): array
    {
        $check->forceFill(['hold_acknowledged_at' => now()])->save();
        $this->audit->record($officer, 'field.hold_acknowledged', 'field_check', $check->id, 'Acknowledged ops room hold', 'done', [], $request);

        return ['message' => 'Hold acknowledged.', 'check' => $this->presenter->workspace($check->fresh(['seats.passenger', 'manifest', 'officer', 'incident']))];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function release(FieldCheck $check, array $payload, Officer $officer, Request $request): array
    {
        $incident = isset($payload['incidentId']) ? Incident::query()->find($payload['incidentId']) : $check->incident;

        $check->forceFill([
            'status' => 'ops_takeover',
            'released_at' => now(),
            'completed_at' => now(),
            'incident_id' => $incident->id ?? $check->incident_id,
            'notes' => $payload['note'] ?? $check->notes,
        ])->save();

        if ($incident) {
            $incident->forceFill(['report_state' => 'under_investigation'])->save();
        }

        $this->refreshCounts($check);
        $this->audit->record($officer, 'field.ops_takeover', 'field_check', $check->id, 'Ops room took over and released the bus', 'done', [], $request);

        return [
            'message' => 'Ops room has taken over. Bus released.',
            'incidentId' => $incident?->id,
            'incidentNumber' => $incident?->incident_number,
            'check' => $this->presenter->record($check->fresh(['seats.passenger', 'manifest', 'officer', 'incident'])),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function clearVehicle(FieldCheck $check, array $payload, Officer $officer, Request $request): array
    {
        $check->loadMissing(['seats.passenger', 'manifest', 'officer']);
        $counts = $this->presenter->counts($check);
        $blocked = $counts['flagged'] + $counts['mismatch'] + $counts['unverified'] + $counts['escalated'] + $counts['pending'];

        if ($blocked > 0) {
            throw NpisApiException::make(409, 'UNRESOLVED_SEATS', 'Resolve all flagged or pending seats before clearing the vehicle.', [
                'blockedSeats' => $blocked,
            ]);
        }

        $reference = $this->clearanceReference();
        $check->forceFill([
            'status' => 'cleared',
            'vehicle_cleared' => true,
            'clearance_reference' => $reference,
            'completed_at' => now(),
            'notes' => $payload['note'] ?? $check->notes,
        ])->save();

        $manifest = $check->manifest;
        if ($manifest) {
            $manifest->forceFill([
                'arrival_status' => 'checked',
                'verdict' => 'clear',
                'checked_at' => now(),
                'checked_by_officer_id' => $officer->id,
                'clearance_reference' => $reference,
                'cleared_at' => now(),
                'checked_count' => $counts['present'] + $counts['clearedWithNote'],
            ])->save();

            FieldManifestEvent::create([
                'manifest_id' => $manifest->id,
                'event_type' => 'vehicle_cleared',
                'location' => $check->dutyPoint->name ?? 'Checkpoint',
                'logged_at' => now(),
                'source' => 'manual',
                'note' => 'Vehicle cleared with reference '.$reference,
                'actor_id' => $officer->id,
            ]);
        }

        $this->audit->record($officer, 'field.vehicle_cleared', 'field_check', $check->id, 'Vehicle cleared '.$reference, 'done', [], $request);

        return [
            'message' => 'Vehicle cleared.',
            'clearanceReference' => $reference,
            'loggedAt' => now()->toIso8601String(),
            'officer' => $officer->full_name.' ('.$officer->service_number.')',
            'counts' => $counts,
            'record' => $this->presenter->record($check->fresh(['seats.passenger', 'manifest', 'officer', 'incident'])),
        ];
    }

    /**
     * @param  list<string>  $fields
     * @return array<string, mixed>
     */
    public function reveal(FieldCheck $check, ManifestPassenger $passenger, array $fields, string $reason, Officer $officer, Request $request): array
    {
        $seat = $this->seat($check, $passenger);
        $values = $this->presenter->reveal($seat, $fields);
        $this->audit->record($officer, 'field.identity_revealed', 'manifest_passenger', $passenger->id, 'Revealed '.implode(', ', $fields).' for seat '.$passenger->seat, 'warning', ['reason' => $reason], $request);

        return [
            'message' => 'Identity fields revealed.',
            'fields' => $values,
            'reason' => $reason,
            'revealedAt' => now()->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function addNote(FieldCheck $check, string $note, Officer $officer, Request $request): array
    {
        $check->forceFill(['notes' => trim(($check->notes ? $check->notes."\n" : '').'['.now()->toIso8601String().'] '.$officer->full_name.': '.$note)])->save();
        $this->audit->record($officer, 'field.check_note', 'field_check', $check->id, 'Added note to check', 'done', [], $request);

        return ['message' => 'Note added.', 'check' => $this->presenter->workspace($check->fresh(['seats.passenger', 'manifest', 'officer', 'incident']))];
    }

    private function seat(FieldCheck $check, ManifestPassenger $passenger): FieldCheckSeat
    {
        $seat = FieldCheckSeat::query()
            ->where('field_check_id', $check->id)
            ->where('manifest_passenger_id', $passenger->id)
            ->first();

        if (! $seat) {
            throw NpisApiException::make(404, 'NOT_FOUND', 'Seat is not part of this check.');
        }

        return $seat;
    }

    /**
     * @return array<string, int>
     */
    private function refreshCounts(FieldCheck $check): array
    {
        $seats = FieldCheckSeat::query()->where('field_check_id', $check->id)->get();
        $counts = [
            'listed' => $seats->count(),
            'present' => $seats->where('status', 'present')->count(),
            'flagged' => $seats->where('status', 'flagged')->count(),
            'mismatch' => $seats->where('status', 'mismatch')->count(),
            'unverified' => $seats->where('status', 'unverified')->count(),
            'escalated' => $seats->where('status', 'escalated')->count(),
            'clearedWithNote' => $seats->where('status', 'cleared_with_note')->count(),
            'pending' => $seats->where('status', 'pending')->count(),
        ];

        $check->forceFill([
            'listed_count' => $counts['listed'],
            'confirmed_count' => $counts['present'] + $counts['clearedWithNote'],
            'flagged_count' => $counts['flagged'],
            'unverified_count' => $counts['unverified'] + $counts['mismatch'],
            'cleared_with_note_count' => $counts['clearedWithNote'],
        ]);

        if (! in_array($check->status, ['on_hold', 'ops_takeover', 'cleared'], true)) {
            $blocked = $counts['flagged'] + $counts['mismatch'] + $counts['unverified'] + $counts['escalated'] + $counts['pending'];
            $check->status = $blocked === 0 ? 'ready' : 'in_progress';
        }

        $check->save();

        return $counts;
    }

    private function createSeatAlert(FieldCheck $check, ManifestPassenger $passenger, FieldCheckSeat $seat, string $reason, string $note, string $status): Alert
    {
        $manifest = $check->manifest;
        $type = in_array($reason, ['watchlist_hit', 'suspected_impersonation', 'priority_escalation'], true) || $status === 'critical'
            ? 'critical'
            : 'flagged';

        $alert = Alert::create([
            'id' => 'al_'.Str::lower((string) Str::ulid()),
            'alert_number' => $this->alertNumber(),
            'type' => $type,
            'title' => 'Seat '.$passenger->seat.' flagged — '.Str::headline($reason),
            'summary' => $note !== '' ? $note : 'Passenger flagged during field manifest check.',
            'subject_type' => 'passenger',
            'subject_id' => $passenger->id,
            'manifest_id' => $check->manifest_id,
            'watchlist_entry_id' => $passenger->watchlist_entry_id,
            'confidence' => $passenger->identity_confidence,
            'mode' => $manifest?->mode,
            'route' => $manifest ? $manifest->route_origin.' → '.$manifest->route_destination : null,
            'duty_point_id' => $check->duty_point_id,
            'state_code' => $manifest?->state_code,
            'lga_id' => $manifest?->lga_id,
            'status' => 'open',
            'sla_seconds' => 120,
            'detected_at' => now(),
            'verified_at' => now(),
            'match_rule' => in_array($reason, ['watchlist_hit', 'priority_escalation'], true) ? 'Exact NIN + name · High risk' : Str::headline($reason),
            'boarding_point' => $passenger->boarding_point,
            'metadata' => [
                'fieldCheckId' => $check->id,
                'seatId' => $seat->id,
                'seat' => $passenger->seat,
                'flagReason' => $reason,
            ],
        ]);

        AlertAction::create([
            'alert_id' => $alert->id,
            'action' => 'flagged',
            'note' => $note !== '' ? $note : 'Seat flagged during field check.',
            'actor_id' => $check->officer_id,
            'metadata' => ['reason' => $reason],
        ]);

        return $alert;
    }

    private function closeSeatAlert(FieldCheckSeat $seat, string $outcome, string $note): void
    {
        if (! $seat->alert_id) {
            return;
        }

        Alert::query()->where('id', $seat->alert_id)->update([
            'status' => 'closed',
            'field_outcome' => $outcome,
            'resolution' => $note,
            'updated_at' => now(),
        ]);
    }

    private function reference(string $prefix): string
    {
        do {
            $reference = $prefix.'-'.now()->format('md').'-'.Str::upper(Str::random(4));
        } while (FieldCheck::query()->where('check_reference', $reference)->orWhere('ops_alert_reference', $reference)->exists());

        return $reference;
    }

    private function escalationReference(): string
    {
        do {
            $reference = 'ESC-'.now()->format('Y').'-'.str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);
        } while (FieldCheck::query()->where('ops_alert_reference', $reference)->exists());

        return $reference;
    }

    private function clearanceReference(): string
    {
        do {
            $reference = 'CLR-'.now()->format('md').'-'.str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);
        } while (FieldCheck::query()->where('clearance_reference', $reference)->exists());

        return $reference;
    }

    private function alertNumber(): string
    {
        do {
            $number = 'AL-'.now()->format('y').'-'.str_pad((string) (Alert::query()->count() + 1), 5, '0', STR_PAD_LEFT);
        } while (Alert::query()->where('alert_number', $number)->exists());

        return $number;
    }
}
