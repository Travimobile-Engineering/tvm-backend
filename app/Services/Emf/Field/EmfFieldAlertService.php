<?php

namespace App\Services\Emf\Field;

use App\Exceptions\NpisApiException;
use App\Models\Emf\Alert;
use App\Models\Emf\AlertAction;
use App\Models\Emf\Officer;
use App\Services\Emf\EmfAuditService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class EmfFieldAlertService
{
    public function __construct(
        private readonly EmfAuditService $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function paginate(Officer $officer, array $filters): array
    {
        $page = max(1, (int) ($filters['page'] ?? 1));
        $pageSize = min(100, max(1, (int) ($filters['pageSize'] ?? 25)));
        $tab = (string) ($filters['tab'] ?? 'active');
        $query = $this->query($officer, $filters, $tab);

        $total = (clone $query)->count();
        $items = $query->with(['manifest', 'watchlistEntry', 'assignee', 'actions', 'fieldCheckSeat.passenger'])
            ->orderByDesc('detected_at')->offset(($page - 1) * $pageSize)->limit($pageSize)->get()
            ->map(fn (Alert $alert): array => $this->contract($alert))->all();

        return [
            'tab' => $tab,
            'counts' => [
                'active' => (clone $this->query($officer, $filters, 'active'))->count(),
                'resolved' => (clone $this->query($officer, $filters, 'resolved'))->count(),
            ],
            'data' => $items,
            'pagination' => [
                'page' => $page,
                'pageSize' => $pageSize,
                'totalItems' => $total,
                'totalPages' => max(1, (int) ceil($total / $pageSize)),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function show(Alert $alert): array
    {
        $alert->load(['manifest.passengers', 'watchlistEntry', 'assignee', 'actions.actor', 'fieldCheckSeat.passenger', 'incident']);

        return $this->contract($alert, true);
    }

    /**
     * @return array<string, mixed>
     */
    public function acknowledge(Alert $alert, Officer $officer, bool $notifyPost, Request $request): array
    {
        if (in_array($alert->status, ['closed', 'dismissed'], true)) {
            throw NpisApiException::make(409, 'INVALID_STATE', 'This alert is already resolved.');
        }

        $alert->forceFill([
            'status' => 'acknowledged',
            'acknowledged_at' => $alert->acknowledged_at ?? now(),
            'assigned_officer_id' => $alert->assigned_officer_id ?? $officer->id,
            'responder_name' => $alert->responder_name ?? $officer->full_name,
            'alert_post' => $notifyPost ? ($officer->dutyPoint->name ?? 'Duty post') : $alert->alert_post,
        ])->save();

        AlertAction::create([
            'alert_id' => $alert->id,
            'action' => $notifyPost ? 'acknowledged_and_post_alerted' : 'acknowledged',
            'note' => $notifyPost ? 'Acknowledged and duty post alerted.' : 'Acknowledged by field officer.',
            'actor_id' => $officer->id,
            'metadata' => ['notifyPost' => $notifyPost],
        ]);
        $this->audit->record($officer, 'field.alert_acknowledged', 'alert', $alert->id, 'Acknowledged '.$alert->alert_number, 'done', [], $request);

        return $this->show($alert->fresh());
    }

    /**
     * @return array<string, mixed>
     */
    public function clear(Alert $alert, Officer $officer, string $outcome, ?string $note, Request $request): array
    {
        if (! in_array($outcome, ['cleared', 'interrupted', 'withdrawn'], true)) {
            throw NpisApiException::make(422, 'VALIDATION_FAILED', 'Choose a valid resolution outcome.', [
                'fields' => ['outcome' => 'Allowed: cleared, interrupted, withdrawn'],
            ]);
        }

        $alert->forceFill([
            'status' => 'closed',
            'field_outcome' => $outcome,
            'resolution' => $note ?? 'Resolved in the field app.',
            'acknowledged_at' => $alert->acknowledged_at ?? now(),
        ])->save();

        AlertAction::create([
            'alert_id' => $alert->id,
            'action' => 'cleared',
            'reason' => $outcome,
            'note' => $note,
            'actor_id' => $officer->id,
            'metadata' => ['outcome' => $outcome],
        ]);
        $this->audit->record($officer, 'field.alert_cleared', 'alert', $alert->id, 'Resolved '.$alert->alert_number.' as '.$outcome, 'done', [], $request);

        return $this->show($alert->fresh());
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<Alert>
     */
    private function query(Officer $officer, array $filters, string $tab): Builder
    {
        $query = Alert::query()
            ->where('state_code', $officer->state_code)
            ->when($filters['dutyPointId'] ?? $officer->duty_point_id, fn (Builder $builder, string $point) => $builder->where('duty_point_id', $point))
            ->when($filters['type'] ?? null, fn (Builder $builder, string $type) => $builder->where('type', $type))
            ->when($filters['search'] ?? null, function (Builder $builder, string $search): void {
                $like = '%'.$search.'%';
                $builder->where(fn (Builder $inner) => $inner->where('title', 'like', $like)->orWhere('alert_number', 'like', $like));
            });

        return $tab === 'resolved'
            ? $query->where(fn (Builder $builder) => $builder->whereIn('status', ['closed', 'dismissed'])->orWhereNotNull('field_outcome'))
            : $query->whereIn('status', ['open', 'acknowledged', 'assigned', 'escalated']);
    }

    /**
     * @return array<string, mixed>
     */
    private function contract(Alert $alert, bool $detail = false): array
    {
        $seat = $alert->fieldCheckSeat;
        $passenger = $seat?->passenger;
        $slaRemaining = max(0, $alert->sla_seconds - (int) $alert->detected_at->diffInSeconds($alert->acknowledged_at ?? now()));

        $data = [
            'alertId' => $alert->id,
            'alertNumber' => $alert->alert_number,
            'type' => $alert->type,
            'title' => $alert->title,
            'summary' => $alert->summary,
            'status' => $alert->status,
            'confidence' => $alert->confidence,
            'matchRule' => $alert->match_rule,
            'route' => $alert->route,
            'boardingPoint' => $alert->boarding_point,
            'mode' => $alert->mode,
            'alertPost' => $alert->alert_post,
            'fieldOutcome' => $alert->field_outcome,
            'detectedAt' => $alert->detected_at->toIso8601String(),
            'acknowledgedAt' => $alert->acknowledged_at?->toIso8601String(),
            'slaRemainingSeconds' => $slaRemaining,
            'slaLabel' => $alert->status === 'open' ? 'SLA '.sprintf('%02d:%02d', intdiv($slaRemaining, 60), $slaRemaining % 60) : null,
            'manifest' => $alert->manifest ? [
                'manifestId' => $alert->manifest->id,
                'manifestNumber' => $alert->manifest->manifest_number,
                'vehicleRegistration' => $alert->manifest->vehicle_registration,
                'operator' => $alert->manifest->operator,
                'mode' => $alert->manifest->mode,
                'route' => $alert->manifest->route_origin.' → '.$alert->manifest->route_destination,
            ] : null,
            'passenger' => $passenger ? [
                'passengerId' => $passenger->id,
                'seat' => $passenger->seat,
                'fullName' => $passenger->full_name,
                'ninMasked' => $passenger->nin ? str_repeat('•', max(0, strlen($passenger->nin) - 4)).substr($passenger->nin, -4) : null,
                'phoneMasked' => $passenger->phone ? str_repeat('•', max(0, strlen($passenger->phone) - 4)).substr($passenger->phone, -4) : null,
                'boardingPoint' => $passenger->boarding_point,
                'photoUrl' => $passenger->photo_url,
                'risk' => $passenger->watchlistEntry?->risk,
            ] : null,
            'responder' => $alert->assignee ? [
                'officerId' => $alert->assignee->id,
                'fullName' => $alert->assignee->full_name,
            ] : null,
            'resolution' => $alert->resolution,
        ];

        if ($detail) {
            $data['timeline'] = $alert->actions->sortBy('created_at')->map(fn (AlertAction $action): array => [
                'action' => $action->action,
                'note' => $action->note,
                'reason' => $action->reason,
                'actor' => $action->actor->full_name ?? 'System',
                'at' => $action->created_at->toIso8601String(),
            ])->values()->all();
            $data['actions'] = $alert->status === 'open'
                ? ['acknowledge', 'clear', 'open_manifest']
                : ($alert->status === 'acknowledged' ? ['clear', 'open_manifest'] : ['open_manifest']);
            $data['incident'] = $alert->incident ? [
                'incidentId' => $alert->incident->id,
                'incidentNumber' => $alert->incident->incident_number,
                'status' => $alert->incident->status,
            ] : null;
        }

        return $data;
    }
}
