<?php

namespace App\Services\Emf;

use App\Exceptions\NpisApiException;
use App\Models\Emf\CaseFile;
use App\Models\Emf\Incident;
use App\Models\Emf\IncidentEvent;
use App\Models\Emf\Officer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class EmfIncidentService
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
        $query = Incident::query()
            ->where('state_code', $filters['stateCode'] ?? $officer->state_code)
            ->when($filters['severity'] ?? null, fn (Builder $q, string $s) => $q->where('severity', $s))
            ->when($filters['status'] ?? null, fn (Builder $q, string $s) => $q->where('status', $s))
            ->when($filters['search'] ?? null, function (Builder $q, string $s): void {
                $like = '%'.$s.'%';
                $q->where(fn (Builder $inner) => $inner->where('incident_number', 'like', $like)->orWhere('title', 'like', $like));
            })
            ->when($filters['period'] ?? null, fn (Builder $q) => $q->where('reported_at', '>=', $this->periodStart($filters['period'])));

        $total = (clone $query)->count();
        $items = $query->with(['manifest', 'responder', 'alert'])->orderByDesc('reported_at')
            ->offset(($page - 1) * $pageSize)->limit($pageSize)->get()
            ->map(fn (Incident $incident): array => $this->contract($incident))->all();

        return ['data' => $items, 'pagination' => $this->pagination($page, $pageSize, $total)];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function create(array $payload, Officer $officer, Request $request): Incident
    {
        $incident = Incident::create([
            'id' => 'inc_'.Str::lower((string) Str::ulid()),
            'incident_number' => $this->nextIncidentNumber(),
            'title' => $payload['title'],
            'type' => $payload['type'],
            'severity' => $payload['severity'],
            'status' => empty($payload['assigneeId']) ? 'unsig' : 'assigned',
            'summary' => $payload['summary'] ?? null,
            'manifest_id' => $payload['manifestId'] ?? null,
            'alert_id' => $payload['alertId'] ?? null,
            'watchlist_entry_id' => $payload['watchlistEntryId'] ?? null,
            'location' => $payload['location'],
            'duty_point_id' => $payload['dutyPointId'] ?? $officer->duty_point_id,
            'state_code' => $officer->state_code,
            'lga_id' => $payload['lgaId'] ?? $officer->lga_id,
            'reporter_name' => $officer->full_name,
            'reported_by' => $officer->id,
            'responder_officer_id' => $payload['assigneeId'] ?? null,
            'reported_at' => now(),
            'metadata' => ['source' => $payload['source'] ?? 'manual'],
        ]);

        IncidentEvent::create([
            'incident_id' => $incident->id,
            'event_type' => 'created',
            'description' => 'Incident reported by '.$officer->full_name,
            'actor_name' => $officer->full_name,
        ]);

        $this->audit->record($officer, 'incident.created', 'incident', $incident->id, 'Created incident '.$incident->incident_number, 'done', [], $request);

        return $incident->fresh(['manifest', 'responder', 'events']);
    }

    /**
     * @return array<string, mixed>
     */
    public function show(Incident $incident): array
    {
        $incident->load(['manifest', 'responder', 'alert', 'events']);
        $data = $this->contract($incident, true);
        $data['timeline'] = $incident->events->sortBy('created_at')->map(fn (IncidentEvent $event): array => [
            'eventId' => $event->id,
            'type' => $event->event_type,
            'description' => $event->description,
            'actor' => $event->actor_name,
            'createdAt' => $event->created_at->toIso8601String(),
        ])->values()->all();
        $data['linkedRecords'] = [
            'manifest' => $incident->manifest ? ['manifestId' => $incident->manifest->id, 'manifestNumber' => $incident->manifest->manifest_number] : null,
            'alert' => $incident->alert ? ['alertId' => $incident->alert->id, 'alertNumber' => $incident->alert->alert_number] : null,
            'case' => CaseFile::query()->where('incident_id', $incident->id)->first()?->only(['id', 'case_number', 'status']),
        ];
        $data['actions'] = ['assign_responder', 'convert_to_case', 'add_note', 'resolve'];

        return $data;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function assign(Incident $incident, array $payload, Officer $officer, Request $request): Incident
    {
        $assignee = Officer::query()->find($payload['officerId']);

        if (! $assignee) {
            throw NpisApiException::make(404, 'NOT_FOUND', 'Officer not found.');
        }

        $incident->forceFill([
            'responder_officer_id' => $assignee->id,
            'status' => 'assigned',
            'acknowledged_at' => $incident->acknowledged_at ?? now(),
        ])->save();

        $this->event($incident, 'assigned', 'Assigned to '.$assignee->full_name.($payload['note'] ?? ''), $officer);
        $this->audit->record($officer, 'incident.assigned', 'incident', $incident->id, 'Assigned '.$incident->incident_number.' to '.$assignee->full_name, 'done', [], $request);

        return $incident->fresh(['responder']);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function convertToCase(Incident $incident, array $payload, Officer $officer, Request $request): array
    {
        $existing = CaseFile::query()->where('incident_id', $incident->id)->first();

        if ($existing) {
            return $this->caseContract($existing);
        }

        $case = CaseFile::create([
            'id' => 'case_'.Str::lower((string) Str::ulid()),
            'case_number' => $this->nextCaseNumber(),
            'title' => $payload['caseTitle'] ?? $incident->title,
            'incident_id' => $incident->id,
            'status' => 'open',
            'severity' => $incident->severity,
            'owner_officer_id' => $payload['ownerId'] ?? $officer->id,
            'opened_at' => now(),
            'due_date' => $payload['dueDate'] ?? null,
            'summary' => $incident->summary,
            'approval_status' => 'none',
            'share_status' => 'none',
            'linked_records' => [
                ['type' => 'incident', 'id' => $incident->id, 'number' => $incident->incident_number],
                ['type' => 'manifest', 'id' => $incident->manifest_id],
                ['type' => 'watchlistEntry', 'id' => $incident->watchlist_entry_id],
            ],
            'metadata' => ['createdFrom' => 'incident', 'convertedBy' => $officer->full_name],
        ]);

        $this->event($incident, 'converted_to_case', 'Case '.$case->case_number.' created', $officer);
        $this->audit->record($officer, 'case.created', 'case', $case->id, 'Converted '.$incident->incident_number.' to '.$case->case_number, 'done', [], $request);

        return $this->caseContract($case);
    }

    public function addNote(Incident $incident, string $note, Officer $officer, Request $request): Incident
    {
        $this->event($incident, 'note', $note, $officer);
        $this->audit->record($officer, 'incident.note_added', 'incident', $incident->id, 'Added note to '.$incident->incident_number, 'done', [], $request);

        return $incident->fresh(['events']);
    }

    public function resolve(Incident $incident, string $note, Officer $officer, Request $request): Incident
    {
        $incident->forceFill(['status' => 'resolved', 'resolved_at' => now(), 'summary' => trim(($incident->summary ?? '')."\n".$note)])->save();
        $this->event($incident, 'resolved', $note, $officer);
        $this->audit->record($officer, 'incident.resolved', 'incident', $incident->id, 'Resolved '.$incident->incident_number, 'done', [], $request);

        return $incident->fresh();
    }

    /**
     * @return array<string, mixed>
     */
    private function contract(Incident $incident, bool $detail = false): array
    {
        $data = [
            'incidentId' => $incident->id,
            'incidentNumber' => $incident->incident_number,
            'title' => $incident->title,
            'type' => $incident->type,
            'severity' => $incident->severity,
            'status' => $incident->status,
            'summary' => $incident->summary,
            'location' => $incident->location,
            'manifest' => $incident->manifest ? ['manifestId' => $incident->manifest->id, 'manifestNumber' => $incident->manifest->manifest_number] : null,
            'responder' => $incident->responder ? ['officerId' => $incident->responder->id, 'fullName' => $incident->responder->full_name] : null,
            'reportedAt' => $incident->reported_at->toIso8601String(),
            'acknowledgedAt' => $incident->acknowledged_at?->toIso8601String(),
            'resolvedAt' => $incident->resolved_at?->toIso8601String(),
        ];

        if ($detail) {
            $data['reportedBy'] = $incident->reporter_name;
            $data['metadata'] = $incident->metadata;
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function caseContract(CaseFile $case): array
    {
        return [
            'caseId' => $case->id,
            'caseNumber' => $case->case_number,
            'title' => $case->title,
            'status' => $case->status,
            'severity' => $case->severity,
            'ownerId' => $case->owner_officer_id,
            'openedAt' => $case->opened_at->toIso8601String(),
            'dueDate' => $case->due_date?->format('Y-m-d'),
        ];
    }

    private function event(Incident $incident, string $type, string $description, Officer $officer): void
    {
        IncidentEvent::create([
            'incident_id' => $incident->id,
            'event_type' => $type,
            'description' => $description,
            'actor_name' => $officer->full_name,
        ]);
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

    private function nextCaseNumber(): string
    {
        $sequence = CaseFile::query()->count() + 1;

        do {
            $number = 'CS-26-'.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
            $sequence++;
        } while (CaseFile::query()->where('case_number', $number)->exists());

        return $number;
    }

    private function periodStart(string $period): Carbon
    {
        return match ($period) {
            '7d' => now()->subDays(7),
            '30d' => now()->subDays(30),
            default => now()->subDay(),
        };
    }

    /**
     * @return array<string, int>
     */
    private function pagination(int $page, int $pageSize, int $total): array
    {
        return ['page' => $page, 'pageSize' => $pageSize, 'totalItems' => $total, 'totalPages' => max(1, (int) ceil($total / $pageSize))];
    }
}
