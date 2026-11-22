<?php

namespace App\Services\Emf;

use App\Exceptions\NpisApiException;
use App\Models\Emf\CaseEvent;
use App\Models\Emf\CaseEvidence;
use App\Models\Emf\CaseFile;
use App\Models\Emf\Officer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class EmfCaseService
{
    public function __construct(private readonly EmfAuditService $audit) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function paginate(array $filters): array
    {
        $page = max(1, (int) ($filters['page'] ?? 1));
        $pageSize = min(100, max(1, (int) ($filters['pageSize'] ?? 25)));
        $query = CaseFile::query()
            ->when($filters['status'] ?? null, fn (Builder $q, string $status) => $q->where('status', $status))
            ->when($filters['severity'] ?? null, fn (Builder $q, string $severity) => $q->where('severity', $severity))
            ->when($filters['assignee'] ?? null, fn (Builder $q, string $owner) => $q->where('owner_officer_id', $owner))
            ->when($filters['search'] ?? null, function (Builder $q, string $search): void {
                $like = '%'.$search.'%';
                $q->where(fn (Builder $inner) => $inner->where('case_number', 'like', $like)->orWhere('title', 'like', $like));
            });

        $total = (clone $query)->count();
        $items = $query->with('owner')->orderByDesc('opened_at')->offset(($page - 1) * $pageSize)->limit($pageSize)
            ->get()->map(fn (CaseFile $case): array => $this->contract($case))->all();

        return [
            'data' => $items,
            'pagination' => $this->pagination($page, $pageSize, $total),
            'board' => [
                'open' => CaseFile::query()->where('status', 'open')->count(),
                'in_review' => CaseFile::query()->where('status', 'in_review')->count(),
                'awaiting_approval' => CaseFile::query()->where('status', 'awaiting_approval')->count(),
                'closed' => CaseFile::query()->where('status', 'closed')->count(),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function create(array $payload, Officer $officer, Request $request): CaseFile
    {
        $case = CaseFile::create([
            'id' => 'case_'.Str::lower((string) Str::ulid()),
            'case_number' => $this->nextCaseNumber(),
            'title' => $payload['title'],
            'status' => 'open',
            'severity' => $payload['severity'],
            'owner_officer_id' => $payload['ownerId'] ?? $officer->id,
            'opened_at' => now(),
            'due_date' => $payload['dueDate'] ?? null,
            'summary' => $payload['summary'] ?? null,
            'approval_status' => 'none',
            'share_status' => 'none',
            'linked_records' => $payload['linkedRecords'] ?? [],
            'metadata' => ['createdBy' => $officer->full_name],
        ]);

        $this->event($case, 'created', 'Case opened', $officer);
        $this->audit->record($officer, 'case.created', 'case', $case->id, 'Created case '.$case->case_number, 'done', [], $request);

        return $case->fresh(['owner', 'events']);
    }

    /**
     * @return array<string, mixed>
     */
    public function show(CaseFile $case): array
    {
        $case->load(['owner', 'events', 'evidence']);
        $data = $this->contract($case, true);
        $data['evidence'] = $case->evidence->map(fn (CaseEvidence $item): array => [
            'evidenceId' => $item->id,
            'type' => $item->evidence_type,
            'label' => $item->label,
            'fileUrl' => URL::temporarySignedRoute(
                'emf.media.evidence',
                now()->addMinutes(15),
                ['evidence' => $item->id],
            ),
            'fileSize' => $item->file_size,
            'uploadedAt' => $item->uploaded_at->toIso8601String(),
        ])->values()->all();
        $data['activity'] = $case->events->sortByDesc('created_at')->map(fn (CaseEvent $event): array => [
            'eventId' => $event->id,
            'action' => $event->action,
            'note' => $event->note,
            'actor' => $event->actor_name,
            'createdAt' => $event->created_at->toIso8601String(),
        ])->values()->all();
        $data['actions'] = ['update', 'add_note', 'add_evidence', 'submit_closure', 'approve_closure', 'send_back', 'request_reopen', 'share', 'close'];

        return $data;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function update(CaseFile $case, array $payload, Officer $officer, Request $request): CaseFile
    {
        $case->fill(array_filter([
            'title' => $payload['title'] ?? null,
            'summary' => $payload['summary'] ?? null,
            'severity' => $payload['severity'] ?? null,
            'owner_officer_id' => $payload['ownerId'] ?? null,
            'due_date' => $payload['dueDate'] ?? null,
        ], static fn ($value): bool => $value !== null))->save();

        $this->event($case, 'updated', 'Case details updated', $officer);
        $this->audit->record($officer, 'case.updated', 'case', $case->id, 'Updated case '.$case->case_number, 'done', [], $request);

        return $case->fresh(['owner', 'events', 'evidence']);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function submitClosure(CaseFile $case, array $payload, Officer $officer, Request $request): CaseFile
    {
        if ($case->status === 'closed') {
            throw NpisApiException::make(409, 'INVALID_STATE', 'This case is already closed.');
        }

        $case->forceFill([
            'status' => 'awaiting_approval',
            'approval_status' => 'requested',
            'metadata' => array_merge($case->metadata ?? [], [
                'closureNote' => $payload['note'] ?? null,
                'shareWithDss' => (bool) ($payload['shareWithDss'] ?? false),
            ]),
        ])->save();

        $this->event($case, 'closure_submitted', $payload['note'] ?? 'Case submitted for closure', $officer);
        $this->audit->record($officer, 'case.closure_submitted', 'case', $case->id, 'Submitted closure for '.$case->case_number, 'done', [], $request);

        return $case->fresh();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function approveClosure(CaseFile $case, array $payload, Officer $officer, Request $request): CaseFile
    {
        if ($case->status !== 'awaiting_approval') {
            throw NpisApiException::make(409, 'INVALID_STATE', 'This case is not awaiting closure approval.');
        }

        $case->forceFill([
            'status' => 'closed',
            'approval_status' => 'approved',
            'approved_by' => $officer->id,
            'approved_at' => now(),
            'closed_at' => now(),
            'share_status' => ($payload['shareWithAgency'] ?? false) ? 'pending' : $case->share_status,
        ])->save();

        $this->event($case, 'closure_approved', $payload['note'] ?? 'Case closure approved', $officer);
        $this->audit->record($officer, 'case.closure_approved', 'case', $case->id, 'Approved closure for '.$case->case_number, 'done', [], $request);

        return $case->fresh();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function sendBack(CaseFile $case, array $payload, Officer $officer, Request $request): CaseFile
    {
        $case->forceFill([
            'status' => 'in_review',
            'approval_status' => 'returned',
            'metadata' => array_merge($case->metadata ?? [], ['returnReason' => $payload['reason'] ?? null]),
        ])->save();

        $this->event($case, 'sent_back', $payload['reason'] ?? 'Case returned to owner', $officer);
        $this->audit->record($officer, 'case.sent_back', 'case', $case->id, 'Sent '.$case->case_number.' back to owner', 'done', [], $request);

        return $case->fresh();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function requestReopen(CaseFile $case, array $payload, Officer $officer, Request $request): CaseFile
    {
        $case->forceFill([
            'status' => 'in_review',
            'approval_status' => 'requested',
            'metadata' => array_merge($case->metadata ?? [], ['reopenReason' => $payload['reason'] ?? null]),
        ])->save();

        $this->event($case, 'reopen_requested', $payload['reason'] ?? 'Reopen requested', $officer);
        $this->audit->record($officer, 'case.reopen_requested', 'case', $case->id, 'Requested reopen for '.$case->case_number, 'done', [], $request);

        return $case->fresh();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function share(CaseFile $case, array $payload, Officer $officer, Request $request): CaseFile
    {
        if ($case->approval_status !== 'approved') {
            throw NpisApiException::make(409, 'APPROVAL_REQUIRED', 'Supervisor approval is required before sharing this case.');
        }

        if (! preg_match('/^\d{6}$/', (string) $payload['code'])) {
            throw NpisApiException::validation('Enter the 6-digit sharing confirmation code.', ['code' => 'Must be 6 digits']);
        }

        $case->forceFill([
            'share_status' => 'active',
            'metadata' => array_merge($case->metadata ?? [], [
                'sharedWith' => $payload['agency'],
                'shareExpiresAt' => $payload['expiresAt'] ?? null,
            ]),
        ])->save();

        $this->event($case, 'shared', 'Shared with '.$payload['agency'], $officer);
        $this->audit->record($officer, 'case.shared', 'case', $case->id, 'Shared '.$case->case_number.' with '.$payload['agency'], 'done', [], $request);

        return $case->fresh();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function close(CaseFile $case, array $payload, Officer $officer, Request $request): CaseFile
    {
        $case->forceFill([
            'status' => 'closed',
            'closed_at' => $case->closed_at ?? now(),
            'approval_status' => $case->approval_status === 'none' ? 'approved' : $case->approval_status,
            'approved_by' => $case->approved_by ?? $officer->id,
            'approved_at' => $case->approved_at ?? now(),
        ])->save();

        $this->event($case, 'closed', $payload['note'] ?? 'Case closed', $officer);
        $this->audit->record($officer, 'case.closed', 'case', $case->id, 'Closed case '.$case->case_number, 'done', [], $request);

        return $case->fresh();
    }

    public function addNote(CaseFile $case, string $note, Officer $officer, Request $request): CaseFile
    {
        $this->event($case, 'note', $note, $officer);
        $this->audit->record($officer, 'case.note_added', 'case', $case->id, 'Added note to '.$case->case_number, 'done', [], $request);

        return $case->fresh(['events']);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function addEvidence(CaseFile $case, array $payload, string $fileUrl, Officer $officer, Request $request): CaseEvidence
    {
        $evidence = CaseEvidence::create([
            'id' => 'evd_'.Str::lower((string) Str::ulid()),
            'case_id' => $case->id,
            'evidence_type' => $payload['type'],
            'label' => $payload['label'],
            'file_url' => $fileUrl,
            'file_size' => $payload['fileSize'] ?? null,
            'uploaded_by' => $officer->id,
            'uploaded_at' => now(),
            'metadata' => $payload['metadata'] ?? [],
        ]);

        $this->event($case, 'evidence_added', 'Evidence added: '.$evidence->label, $officer);
        $this->audit->record($officer, 'case.evidence_added', 'case', $case->id, 'Added evidence to '.$case->case_number, 'done', [], $request);

        return $evidence;
    }

    /**
     * @return array<string, mixed>
     */
    private function contract(CaseFile $case, bool $detail = false): array
    {
        $data = [
            'caseId' => $case->id,
            'caseNumber' => $case->case_number,
            'title' => $case->title,
            'status' => $case->status,
            'severity' => $case->severity,
            'owner' => $case->owner ? ['officerId' => $case->owner->id, 'fullName' => $case->owner->full_name] : null,
            'openedAt' => $case->opened_at->toIso8601String(),
            'closedAt' => $case->closed_at?->toIso8601String(),
            'dueDate' => $case->due_date?->format('Y-m-d'),
            'approvalStatus' => $case->approval_status,
            'shareStatus' => $case->share_status,
            'linkedRecords' => $case->linked_records ?? [],
        ];

        if ($detail) {
            $data['summary'] = $case->summary;
            $data['approvedBy'] = $case->approved_by;
            $data['approvedAt'] = $case->approved_at?->toIso8601String();
            $data['metadata'] = $case->metadata;
        }

        return $data;
    }

    private function event(CaseFile $case, string $action, string $note, Officer $officer): void
    {
        CaseEvent::create([
            'case_id' => $case->id,
            'action' => $action,
            'note' => $note,
            'actor_name' => $officer->full_name,
        ]);
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

    /**
     * @return array<string, int>
     */
    private function pagination(int $page, int $pageSize, int $total): array
    {
        return ['page' => $page, 'pageSize' => $pageSize, 'totalItems' => $total, 'totalPages' => max(1, (int) ceil($total / $pageSize))];
    }
}
