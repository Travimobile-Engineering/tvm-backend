<?php

namespace App\Services\Emf\Field;

use App\Exceptions\NpisApiException;
use App\Models\Emf\Alert;
use App\Models\Emf\Incident;
use App\Models\Emf\IncidentAttachment;
use App\Models\Emf\IncidentEvent;
use App\Models\Emf\Manifest;
use App\Models\Emf\Officer;
use App\Services\Emf\EmfAuditService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class EmfFieldReportService
{
    public function __construct(private readonly EmfAuditService $audit) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function paginate(Officer $officer, array $filters): array
    {
        $page = max(1, (int) ($filters['page'] ?? 1));
        $pageSize = min(100, max(1, (int) ($filters['pageSize'] ?? 25)));
        $tab = (string) ($filters['tab'] ?? 'open');
        $query = Incident::query()
            ->where('reported_by', $officer->id)
            ->when($filters['search'] ?? null, function (Builder $builder, string $search): void {
                $like = '%'.$search.'%';
                $builder->where(fn (Builder $inner) => $inner->where('incident_number', 'like', $like)
                    ->orWhere('title', 'like', $like)->orWhere('linked_vehicle', 'like', $like));
            })
            ->when($filters['severity'] ?? null, fn (Builder $builder, string $severity) => $builder->where('severity', $severity));

        if ($tab === 'closed') {
            $query->where(fn (Builder $builder) => $builder->where('report_state', 'closed')->orWhere('status', 'resolved'));
        } else {
            $query->where('report_state', '!=', 'closed')->where('status', '!=', 'resolved');
        }

        $total = (clone $query)->count();
        $items = $query->with(['manifest', 'attachments', 'events'])->orderByDesc('reported_at')
            ->offset(($page - 1) * $pageSize)->limit($pageSize)->get()
            ->map(fn (Incident $incident): array => $this->contract($incident))->all();

        return [
            'tab' => $tab,
            'counts' => [
                'open' => Incident::query()->where('reported_by', $officer->id)->where('report_state', '!=', 'closed')->where('status', '!=', 'resolved')->count(),
                'closed' => Incident::query()->where('reported_by', $officer->id)->where(fn (Builder $builder) => $builder->where('report_state', 'closed')->orWhere('status', 'resolved'))->count(),
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
    public function show(Incident $incident): array
    {
        $incident->load(['manifest', 'attachments', 'events', 'responder', 'alert']);

        return $this->contract($incident, true);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function create(array $payload, Officer $officer, Request $request): Incident
    {
        return DB::transaction(function () use ($payload, $officer, $request): Incident {
            if (! empty($payload['clientReportId'])) {
                $existing = Incident::query()->where('client_report_id', $payload['clientReportId'])->first();

                if ($existing) {
                    return $existing->load(['manifest', 'attachments', 'events']);
                }
            }

            $manifest = isset($payload['manifestId']) ? Manifest::query()->find($payload['manifestId']) : null;
            $category = (string) ($payload['category'] ?? $payload['type'] ?? 'other');
            $offline = (bool) ($payload['saveOffline'] ?? false);
            $escalate = (bool) ($payload['escalateToOps'] ?? false);
            $reportState = $offline ? 'queued' : ($escalate ? 'escalated' : 'open');

            $incident = Incident::create([
                'id' => 'inc_'.Str::lower((string) Str::ulid()),
                'incident_number' => $this->nextIncidentNumber(),
                'title' => $payload['title'] ?? Str::headline($category).' report',
                'type' => $category,
                'category' => $category,
                'severity' => $payload['severity'] ?? 'medium',
                'status' => $escalate ? 'in_progress' : 'unsig',
                'report_state' => $reportState,
                'summary' => $payload['note'] ?? $payload['summary'] ?? null,
                'manifest_id' => $manifest?->id,
                'alert_id' => null,
                'watchlist_entry_id' => $payload['watchlistEntryId'] ?? null,
                'location' => $payload['location'] ?? $officer->dutyPoint->name ?? 'Checkpoint',
                'duty_point_id' => $payload['dutyPointId'] ?? $officer->duty_point_id,
                'state_code' => $officer->state_code,
                'lga_id' => $officer->lga_id,
                'reporter_name' => $officer->full_name,
                'reported_by' => $officer->id,
                'responder_officer_id' => $payload['assigneeId'] ?? null,
                'reported_at' => now(),
                'occurred_at' => isset($payload['occurredAt']) ? Carbon::parse($payload['occurredAt']) : now(),
                'gps_latitude' => $payload['gpsLatitude'] ?? null,
                'gps_longitude' => $payload['gpsLongitude'] ?? null,
                'linked_vehicle' => $payload['linkedVehicle'] ?? $manifest?->vehicle_registration,
                'client_report_id' => $payload['clientReportId'] ?? null,
                'signed_at' => $offline ? null : now(),
                'signed_by' => $offline ? null : $officer->id,
                'metadata' => [
                    'seat' => $payload['seat'] ?? null,
                    'seatId' => $payload['seatId'] ?? null,
                    'fieldCheckId' => $payload['fieldCheckId'] ?? null,
                    'localId' => $payload['localId'] ?? null,
                    'offline' => $offline,
                    'actionTaken' => $payload['actionTaken'] ?? null,
                ],
            ]);

            IncidentEvent::create([
                'incident_id' => $incident->id,
                'event_type' => 'created',
                'description' => 'Report filed by '.$officer->full_name.'.',
                'actor_name' => $officer->full_name,
            ]);

            foreach ((array) ($payload['evidence'] ?? []) as $evidence) {
                if (! is_array($evidence) || empty($evidence['fileUrl'])) {
                    continue;
                }
                $this->createAttachment($incident, [
                    'type' => $evidence['type'] ?? 'photo',
                    'fileUrl' => $evidence['fileUrl'],
                    'fileSize' => $evidence['fileSize'] ?? null,
                    'durationSeconds' => $evidence['durationSeconds'] ?? null,
                ], $officer);
            }

            if ($escalate) {
                $alert = Alert::create([
                    'id' => 'al_'.Str::lower((string) Str::ulid()),
                    'alert_number' => $this->nextAlertNumber(),
                    'type' => 'critical',
                    'title' => $incident->title,
                    'summary' => $incident->summary,
                    'subject_type' => 'incident',
                    'subject_id' => $incident->id,
                    'manifest_id' => $manifest?->id,
                    'confidence' => null,
                    'mode' => $manifest?->mode,
                    'route' => $manifest ? $manifest->route_origin.' → '.$manifest->route_destination : null,
                    'duty_point_id' => $incident->duty_point_id,
                    'state_code' => $incident->state_code,
                    'lga_id' => $incident->lga_id,
                    'status' => 'escalated',
                    'sla_seconds' => 120,
                    'detected_at' => now(),
                    'verified_at' => now(),
                    'metadata' => ['incidentId' => $incident->id, 'source' => 'field_report'],
                ]);
                $incident->forceFill(['alert_id' => $alert->id])->save();
            }

            $this->audit->record($officer, 'field.report_filed', 'incident', $incident->id, 'Filed report '.$incident->incident_number, $offline ? 'warning' : 'done', ['offline' => $offline], $request);

            return $incident->fresh(['manifest', 'attachments', 'events']);
        });
    }

    /**
     * @param  list<array<string, mixed>>  $reports
     * @return array<string, mixed>
     */
    public function sync(array $reports, Officer $officer, Request $request): array
    {
        $synced = [];

        foreach ($reports as $report) {
            if (! is_array($report)) {
                continue;
            }

            $localId = $report['localId'] ?? $report['clientReportId'] ?? null;
            $payload = $report['payload'] ?? $report;
            $payload['clientReportId'] ??= $localId;
            $payload['saveOffline'] = false;
            $incident = $this->create($payload, $officer, $request);
            $synced[] = [
                'localId' => $localId,
                'incidentId' => $incident->id,
                'incidentNumber' => $incident->incident_number,
                'reportState' => $incident->report_state,
            ];
        }

        return ['synced' => $synced, 'count' => count($synced)];
    }

    /**
     * @return array<string, mixed>
     */
    public function addNote(Incident $incident, string $note, Officer $officer, Request $request): array
    {
        IncidentEvent::create([
            'incident_id' => $incident->id,
            'event_type' => 'note',
            'description' => $note,
            'actor_name' => $officer->full_name,
        ]);
        $this->audit->record($officer, 'field.report_note', 'incident', $incident->id, 'Added note to report '.$incident->incident_number, 'done', [], $request);

        return $this->show($incident->fresh());
    }

    /**
     * @return array<string, mixed>
     */
    public function sign(Incident $incident, Officer $officer, Request $request): array
    {
        $incident->forceFill([
            'signed_at' => now(),
            'signed_by' => $officer->id,
            'report_state' => $incident->report_state === 'queued' ? 'open' : $incident->report_state,
        ])->save();

        IncidentEvent::create([
            'incident_id' => $incident->id,
            'event_type' => 'signed',
            'description' => 'Report signed and locked by '.$officer->full_name,
            'actor_name' => $officer->full_name,
        ]);
        $this->audit->record($officer, 'field.report_signed', 'incident', $incident->id, 'Signed report '.$incident->incident_number, 'done', [], $request);

        return $this->show($incident->fresh());
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function addAttachment(Incident $incident, array $payload, Officer $officer, Request $request): IncidentAttachment
    {
        $attachment = $this->createAttachment($incident, $payload, $officer);
        IncidentEvent::create([
            'incident_id' => $incident->id,
            'event_type' => 'evidence_added',
            'description' => 'Evidence added: '.$attachment->attachment_type,
            'actor_name' => $officer->full_name,
        ]);
        $this->audit->record($officer, 'field.report_evidence', 'incident', $incident->id, 'Added evidence to '.$incident->incident_number, 'done', [], $request);

        return $attachment;
    }

    /**
     * @return array<string, mixed>
     */
    public function meta(): array
    {
        return [
            'incidentTypes' => [
                ['key' => 'unregistered_manifest', 'label' => 'Unregistered manifest'],
                ['key' => 'suspicious_person', 'label' => 'Suspicious person'],
                ['key' => 'document_nin_fraud', 'label' => 'Document / NIN fraud'],
                ['key' => 'watchlist_hit', 'label' => 'Watchlist hit'],
                ['key' => 'overloading_unmanifested', 'label' => 'Overloading / unmanifested'],
                ['key' => 'vehicle_evaded_stop', 'label' => 'Vehicle evaded stop'],
                ['key' => 'security_threat_distress', 'label' => 'Security threat / distress'],
            ],
            'severities' => [
                ['key' => 'low', 'label' => 'Low'],
                ['key' => 'medium', 'label' => 'Medium'],
                ['key' => 'high', 'label' => 'High'],
                ['key' => 'critical', 'label' => 'Critical'],
            ],
            'evidenceTypes' => [
                ['key' => 'photo', 'label' => 'Photo'],
                ['key' => 'voice_note', 'label' => 'Voice note'],
                ['key' => 'document', 'label' => 'Document'],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function createAttachment(Incident $incident, array $payload, Officer $officer): IncidentAttachment
    {
        $url = (string) ($payload['fileUrl'] ?? '');

        if ($url === '') {
            throw NpisApiException::validation('Provide evidence fileUrl or upload a file.', ['file' => 'Required']);
        }

        $type = (string) ($payload['type'] ?? 'photo');
        if (! in_array($type, ['photo', 'voice_note', 'document'], true)) {
            $type = 'photo';
        }

        return IncidentAttachment::create([
            'id' => 'att_'.Str::lower((string) Str::ulid()),
            'incident_id' => $incident->id,
            'attachment_type' => $type,
            'file_url' => $url,
            'file_size' => $payload['fileSize'] ?? null,
            'duration_seconds' => $payload['durationSeconds'] ?? null,
            'uploaded_by' => $officer->id,
            'metadata' => $payload['metadata'] ?? [],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function contract(Incident $incident, bool $detail = false): array
    {
        $metadata = $incident->metadata ?? [];
        $stateLabel = match ($incident->report_state) {
            'queued', 'saved_offline' => 'Saved offline',
            'escalated' => 'Escalated to Ops',
            'under_investigation' => 'Under investigation',
            'signing' => 'Signing',
            'closed' => 'Closed',
            default => 'Open',
        };

        $data = [
            'reportId' => $incident->id,
            'reportNumber' => $incident->incident_number,
            'title' => $incident->title,
            'category' => $incident->category,
            'type' => $incident->type,
            'severity' => $incident->severity,
            'status' => $incident->status,
            'reportState' => $incident->report_state,
            'statusLabel' => $stateLabel,
            'summary' => $incident->summary,
            'location' => $incident->location,
            'vehicle' => $incident->linked_vehicle,
            'seat' => $metadata['seat'] ?? null,
            'signedAt' => $incident->signed_at?->toIso8601String(),
            'signedBy' => $incident->signed_by,
            'reportedAt' => $incident->reported_at->toIso8601String(),
            'occurredAt' => $incident->occurred_at?->toIso8601String(),
            'savedOffline' => $incident->report_state === 'queued',
            'manifest' => $incident->manifest ? [
                'manifestId' => $incident->manifest->id,
                'manifestNumber' => $incident->manifest->manifest_number,
                'vehicleRegistration' => $incident->manifest->vehicle_registration,
            ] : null,
            'evidenceCount' => $incident->attachments->count(),
            'locked' => $incident->signed_at !== null,
            'actions' => $incident->signed_at === null ? ['sign', 'add_note', 'add_evidence'] : ['add_note', 'add_evidence'],
        ];

        if ($detail) {
            $data['whatHappened'] = $incident->summary;
            $data['evidence'] = $incident->attachments->map(fn (IncidentAttachment $attachment): array => [
                'attachmentId' => $attachment->id,
                'type' => $attachment->attachment_type,
                'fileUrl' => URL::temporarySignedRoute('emf.field.media.attachment', now()->addMinutes(15), ['attachment' => $attachment->id]),
                'fileSize' => $attachment->file_size,
                'durationSeconds' => $attachment->duration_seconds,
                'createdAt' => $attachment->created_at?->toIso8601String(),
            ])->values()->all();
            $data['timeline'] = $incident->events->sortBy('created_at')->map(fn (IncidentEvent $event): array => [
                'type' => $event->event_type,
                'description' => $event->description,
                'actor' => $event->actor_name,
                'at' => $event->created_at->toIso8601String(),
            ])->values()->all();
            $data['reportLink'] = $incident->incident_number;
        }

        return $data;
    }

    private function nextIncidentNumber(): string
    {
        do {
            $number = 'INC-'.now()->format('Y').'-'.str_pad((string) (Incident::query()->count() + 1), 4, '0', STR_PAD_LEFT);
        } while (Incident::query()->where('incident_number', $number)->exists());

        return $number;
    }

    private function nextAlertNumber(): string
    {
        do {
            $number = 'AL-'.now()->format('y').'-'.str_pad((string) (Alert::query()->count() + 1), 5, '0', STR_PAD_LEFT);
        } while (Alert::query()->where('alert_number', $number)->exists());

        return $number;
    }
}
