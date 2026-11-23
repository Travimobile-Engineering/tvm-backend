<?php

namespace App\Services\Emf;

use App\Exceptions\NpisApiException;
use App\Models\Emf\Alert;
use App\Models\Emf\AlertAction;
use App\Models\Emf\Incident;
use App\Models\Emf\IncidentEvent;
use App\Models\Emf\Officer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class EmfAlertService
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
        $query = $this->query($filters, $officer);

        $total = (clone $query)->count();
        $items = $query->with(['manifest', 'watchlistEntry', 'assignee'])
            ->orderByDesc('detected_at')->offset(($page - 1) * $pageSize)->limit($pageSize)->get()
            ->map(fn (Alert $alert): array => $this->contract($alert))->all();

        return [
            'data' => $items,
            'pagination' => $this->pagination($page, $pageSize, $total),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function show(Alert $alert): array
    {
        $alert->load(['manifest.passengers', 'manifest.checks', 'watchlistEntry.documents', 'assignee', 'actions.actor', 'incident']);
        $data = $this->contract($alert, true);
        $data['checks'] = $alert->manifest?->checks->map(fn ($check): array => [
            'checkId' => $check->id,
            'type' => $check->type,
            'status' => $check->status,
            'result' => $check->result,
            'performedAt' => $check->performed_at->toIso8601String(),
        ])->values()->all() ?? [];
        $data['actions'] = $alert->actions->sortByDesc('created_at')->map(fn (AlertAction $action): array => [
            'action' => $action->action,
            'note' => $action->note,
            'reason' => $action->reason,
            'actor' => $action->actor->full_name ?? 'System',
            'createdAt' => $action->created_at->toIso8601String(),
        ])->values()->all();
        $data['manifestDetail'] = $alert->manifest ? [
            'manifestId' => $alert->manifest->id,
            'manifestNumber' => $alert->manifest->manifest_number,
            'mode' => $alert->manifest->mode,
            'operator' => $alert->manifest->operator,
            'route' => $alert->manifest->route_origin.' → '.$alert->manifest->route_destination,
            'vehicleRegistration' => $alert->manifest->vehicle_registration,
            'departedAt' => $alert->manifest->departed_at?->toIso8601String(),
            'expectedArrivalAt' => $alert->manifest->expected_arrival_at?->toIso8601String(),
            'passengers' => $alert->manifest->passengers->take(8)->map(fn ($passenger): array => [
                'fullName' => $passenger->full_name,
                'nin' => $passenger->nin,
                'seat' => $passenger->seat,
                'verdict' => $passenger->verdict,
            ])->values()->all(),
        ] : null;
        $data['incident'] = $alert->incident ? [
            'incidentId' => $alert->incident->id,
            'incidentNumber' => $alert->incident->incident_number,
            'status' => $alert->incident->status,
        ] : null;

        return $data;
    }

    public function acknowledge(Alert $alert, Officer $officer, Request $request): Alert
    {
        if (in_array($alert->status, ['dismissed', 'closed'], true)) {
            throw NpisApiException::make(409, 'INVALID_STATE', 'This alert is already closed.');
        }

        $alert->forceFill([
            'status' => 'acknowledged',
            'acknowledged_at' => $alert->acknowledged_at ?? now(),
            'assigned_officer_id' => $alert->assigned_officer_id ?? $officer->id,
            'responder_name' => $alert->responder_name ?? $officer->full_name,
        ])->save();

        $this->logAction($alert, $officer, 'acknowledged', null, null);

        return $alert->fresh();
    }

    public function dismiss(Alert $alert, Officer $officer, string $reason, ?string $note, Request $request): Alert
    {
        if (in_array($alert->status, ['dismissed', 'closed'], true)) {
            throw NpisApiException::make(409, 'INVALID_STATE', 'This alert is already closed.');
        }

        $alert->forceFill([
            'status' => 'dismissed',
            'resolution' => $note,
            'metadata' => array_merge($alert->metadata ?? [], ['dismissReason' => $reason]),
        ])->save();

        $this->logAction($alert, $officer, 'dismissed', $reason, $note);
        $this->audit->record($officer, 'alert.dismissed', 'alert', $alert->id, 'Dismissed '.$alert->alert_number.' as false positive', 'done', ['reason' => $reason], $request);

        return $alert->fresh();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function escalate(Alert $alert, Officer $officer, array $payload, Request $request): Alert
    {
        if ($alert->incident) {
            throw NpisApiException::make(409, 'INVALID_STATE', 'This alert has already been escalated.');
        }

        $incident = Incident::create([
            'id' => 'inc_'.Str::lower((string) Str::ulid()),
            'incident_number' => $this->nextIncidentNumber(),
            'title' => $alert->title,
            'type' => $alert->type,
            'severity' => $payload['severity'],
            'status' => empty($payload['assigneeId']) ? 'unsig' : 'assigned',
            'summary' => $payload['notes'] ?? $alert->summary,
            'manifest_id' => $alert->manifest_id,
            'alert_id' => $alert->id,
            'watchlist_entry_id' => $alert->watchlist_entry_id,
            'location' => $alert->route ?? 'Unknown location',
            'duty_point_id' => $alert->duty_point_id,
            'state_code' => $alert->state_code,
            'lga_id' => $alert->lga_id,
            'reporter_name' => $officer->full_name,
            'reported_by' => $officer->id,
            'responder_officer_id' => $payload['assigneeId'] ?? null,
            'reported_at' => now(),
            'metadata' => ['sourceAlert' => $alert->alert_number],
        ]);

        IncidentEvent::create([
            'incident_id' => $incident->id,
            'event_type' => 'created',
            'description' => 'Incident created from alert '.$alert->alert_number,
            'actor_name' => $officer->full_name,
        ]);

        $alert->forceFill([
            'status' => 'escalated',
            'acknowledged_at' => $alert->acknowledged_at ?? now(),
            'assigned_officer_id' => $payload['assigneeId'] ?? $alert->assigned_officer_id,
            'responder_name' => isset($payload['assigneeId']) ? Officer::query()->find($payload['assigneeId'])?->full_name : $alert->responder_name,
        ])->save();

        $this->logAction($alert, $officer, 'escalated', null, $payload['notes'] ?? null, ['incidentId' => $incident->id, 'severity' => $payload['severity']]);
        $this->audit->record($officer, 'alert.escalated', 'incident', $incident->id, 'Escalated '.$alert->alert_number.' to '.$incident->incident_number, 'done', [], $request);

        return $alert->fresh(['incident']);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function assign(Alert $alert, Officer $officer, array $payload, Request $request): Alert
    {
        $assignee = Officer::query()->find($payload['officerId']);

        if (! $assignee) {
            throw NpisApiException::make(404, 'NOT_FOUND', 'Officer not found.');
        }

        $alert->forceFill([
            'status' => 'assigned',
            'assigned_officer_id' => $assignee->id,
            'responder_name' => $assignee->full_name,
            'acknowledged_at' => $alert->acknowledged_at ?? now(),
        ])->save();

        if ($alert->incident) {
            $alert->incident->forceFill(['responder_officer_id' => $assignee->id, 'status' => 'assigned'])->save();
        }

        $this->logAction($alert, $officer, 'assigned', $payload['actionType'] ?? null, $payload['note'] ?? null, [
            'assigneeId' => $assignee->id,
            'assigneeName' => $assignee->full_name,
        ]);
        $this->audit->record($officer, 'alert.assigned', 'alert', $alert->id, 'Assigned '.$alert->alert_number.' to '.$assignee->full_name, 'done', [], $request);

        return $alert->fresh();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<Alert>
     */
    private function query(array $filters, Officer $officer): Builder
    {
        $from = match ($filters['period'] ?? '24h') {
            '7d' => now()->subDays(7),
            '30d' => now()->subDays(30),
            default => now()->subDay(),
        };

        return Alert::query()
            ->where('state_code', $filters['stateCode'] ?? $officer->state_code)
            ->when($filters['lgaId'] ?? null, fn (Builder $q, string $lga) => $q->where('lga_id', $lga))
            ->when($filters['type'] ?? null, fn (Builder $q, string $type) => $q->where('type', $type))
            ->when($filters['status'] ?? null, fn (Builder $q, string $status) => $q->where('status', $status))
            ->when($filters['mode'] ?? null, fn (Builder $q, string $mode) => $q->where('mode', $mode))
            ->where('detected_at', '>=', $from);
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function logAction(Alert $alert, Officer $officer, string $action, ?string $reason, ?string $note, array $extra = []): void
    {
        AlertAction::create([
            'alert_id' => $alert->id,
            'action' => $action,
            'reason' => $reason,
            'note' => $note,
            'actor_id' => $officer->id,
            'metadata' => $extra,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function contract(Alert $alert, bool $detail = false): array
    {
        $slaRemaining = max(0, $alert->sla_seconds - (int) $alert->detected_at->diffInSeconds($alert->acknowledged_at ?? now()));
        $subject = $alert->watchlistEntry;

        $data = [
            'alertId' => $alert->id,
            'alertNumber' => $alert->alert_number,
            'type' => $alert->type,
            'title' => $alert->title,
            'summary' => $alert->summary,
            'confidence' => $alert->confidence,
            'mode' => $alert->mode,
            'route' => $alert->route,
            'status' => $alert->status,
            'detectedAt' => $alert->detected_at->toIso8601String(),
            'verifiedAt' => $alert->verified_at?->toIso8601String(),
            'acknowledgedAt' => $alert->acknowledged_at?->toIso8601String(),
            'slaSeconds' => $alert->sla_seconds,
            'slaRemainingSeconds' => $slaRemaining,
            'slaLabel' => $alert->status === 'open' ? 'SLA '.sprintf('%02d:%02d', intdiv($slaRemaining, 60), $slaRemaining % 60) : null,
            'subject' => $subject ? [
                'watchlistEntryId' => $subject->id,
                'entryNumber' => $subject->entry_number,
                'fullName' => $subject->full_name,
                'nin' => $subject->nin,
                'phone' => $subject->phone,
                'risk' => $subject->risk,
                'reason' => $subject->reason,
                'source' => $subject->source,
                'expiresAt' => $subject->expires_at?->toIso8601String(),
            ] : null,
            'manifest' => $alert->manifest ? [
                'manifestId' => $alert->manifest->id,
                'manifestNumber' => $alert->manifest->manifest_number,
                'operator' => $alert->manifest->operator,
                'route' => $alert->manifest->route_origin.' → '.$alert->manifest->route_destination,
            ] : null,
            'responder' => $alert->assignee ? [
                'officerId' => $alert->assignee->id,
                'fullName' => $alert->assignee->full_name,
                'serviceNumber' => $alert->assignee->service_number,
            ] : null,
            'resolution' => $alert->resolution,
        ];

        if ($detail) {
            $data['enforcementActions'] = [
                ['action' => 'dismiss', 'label' => 'Dismiss as false positive'],
                ['action' => 'escalate', 'label' => 'Escalate to incident'],
                ['action' => 'assign', 'label' => 'Assign responder'],
            ];
        }

        return $data;
    }

    private function nextIncidentNumber(): string
    {
        $sequence = Incident::query()->count() + 1;

        do {
            $number = 'INC-26-'.str_pad((string) $sequence, 5, '0', STR_PAD_LEFT);
            $sequence++;
        } while (Incident::query()->where('incident_number', $number)->exists());

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
