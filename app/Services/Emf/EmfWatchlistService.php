<?php

namespace App\Services\Emf;

use App\Exceptions\NpisApiException;
use App\Models\Emf\CaseFile;
use App\Models\Emf\Incident;
use App\Models\Emf\ManifestPassenger;
use App\Models\Emf\Officer;
use App\Models\Emf\WatchlistDocument;
use App\Models\Emf\WatchlistEntry;
use App\Models\Emf\WatchlistSubscription;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class EmfWatchlistService
{
    public function __construct(private readonly EmfAuditService $audit) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array{data: list<array<string, mixed>>, pagination: array<string, int>}
     */
    public function paginate(array $filters): array
    {
        $page = max(1, (int) ($filters['page'] ?? 1));
        $pageSize = min(100, max(1, (int) ($filters['pageSize'] ?? 25)));
        $query = WatchlistEntry::query()
            ->when($filters['type'] ?? null, fn (Builder $q, string $type) => $q->where('type', $type))
            ->when($filters['risk'] ?? null, fn (Builder $q, string $risk) => $q->where('risk', $risk))
            ->when($filters['status'] ?? null, fn (Builder $q, string $status) => $q->where('status', $status))
            ->when($filters['search'] ?? null, function (Builder $q, string $search): void {
                $like = '%'.$search.'%';
                $q->where(fn (Builder $inner) => $inner->where('full_name', 'like', $like)
                    ->orWhere('entry_number', 'like', $like)->orWhere('nin', 'like', $like)
                    ->orWhere('phone', 'like', $like)->orWhere('plate_number', 'like', $like));
            });

        $total = (clone $query)->count();
        $items = $query->orderByDesc('created_at')->offset(($page - 1) * $pageSize)->limit($pageSize)
            ->get()->map(fn (WatchlistEntry $entry): array => $this->contract($entry))->all();

        return ['data' => $items, 'pagination' => $this->pagination($page, $pageSize, $total)];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function create(array $payload, Officer $officer, Request $request): WatchlistEntry
    {
        $entry = WatchlistEntry::create([
            'id' => 'wl_'.Str::lower((string) Str::ulid()),
            'entry_number' => $this->nextEntryNumber(),
            'type' => $payload['type'],
            'risk' => $payload['risk'],
            'reason' => $payload['reason'],
            'source' => $payload['source'],
            'full_name' => $payload['fullName'] ?? null,
            'nin' => $payload['nin'] ?? null,
            'phone' => $payload['phone'] ?? null,
            'plate_number' => $payload['plateNumber'] ?? null,
            'dob' => $payload['dob'] ?? null,
            'nationality' => $payload['nationality'] ?? 'Nigerian',
            'address' => $payload['address'] ?? null,
            'expires_at' => $payload['expiresAt'] ?? null,
            'status' => 'active',
            'notes' => $payload['notes'] ?? null,
            'created_by' => $officer->id,
        ]);

        $this->audit->record($officer, 'watchlist.created', 'watchlist', $entry->id, 'Created watchlist entry '.$entry->entry_number, 'done', ['risk' => $entry->risk], $request);

        return $entry;
    }

    /**
     * @return array<string, mixed>
     */
    public function show(WatchlistEntry $entry): array
    {
        $entry->load('documents');
        $data = $this->contract($entry, true);
        $data['travelHistory'] = ManifestPassenger::query()
            ->where(function (Builder $q) use ($entry): void {
                if ($entry->nin) {
                    $q->where('nin', $entry->nin);
                }
                if ($entry->full_name) {
                    $q->orWhere('full_name', $entry->full_name);
                }
            })
            ->with('manifest')
            ->orderByDesc('created_at')
            ->limit(8)
            ->get()
            ->map(fn (ManifestPassenger $p): array => [
                'manifestNumber' => $p->manifest?->manifest_number,
                'manifestId' => $p->manifest?->id,
                'mode' => $p->manifest?->mode,
                'route' => $p->manifest ? $p->manifest->route_origin.' → '.$p->manifest->route_destination : null,
                'date' => $p->manifest?->checked_at?->toIso8601String(),
                'verdict' => $p->verdict,
            ])->values()->all();
        $data['linkedRecords'] = [
            'alerts' => $entry->alerts()->orderByDesc('detected_at')->limit(5)->get()->map(fn ($a): array => [
                'alertId' => $a->id, 'alertNumber' => $a->alert_number, 'type' => $a->type, 'status' => $a->status,
            ])->values()->all(),
            'incidents' => Incident::query()->where('watchlist_entry_id', $entry->id)->limit(5)->get()->map(fn (Incident $i): array => [
                'incidentId' => $i->id, 'incidentNumber' => $i->incident_number, 'status' => $i->status,
            ])->values()->all(),
            'cases' => CaseFile::query()->whereJsonContains('linked_records', ['watchlistEntryId' => $entry->id])->limit(5)->get()->map(fn (CaseFile $c): array => [
                'caseId' => $c->id, 'caseNumber' => $c->case_number, 'status' => $c->status,
            ])->values()->all(),
        ];
        $data['documents'] = $entry->documents->map(fn (WatchlistDocument $document): array => [
            'documentId' => $document->id,
            'name' => $document->name,
            'fileUrl' => $document->file_url,
            'fileSize' => $document->file_size,
        ])->values()->all();

        return $data;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function update(WatchlistEntry $entry, array $payload, Officer $officer, Request $request): WatchlistEntry
    {
        $this->verifyEditCode($officer, (string) $payload['verificationCode']);

        $entry->fill([
            'type' => $payload['type'] ?? $entry->type,
            'risk' => $payload['risk'] ?? $entry->risk,
            'reason' => $payload['reason'] ?? $entry->reason,
            'source' => $payload['source'] ?? $entry->source,
            'full_name' => $payload['fullName'] ?? $entry->full_name,
            'nin' => $payload['nin'] ?? $entry->nin,
            'phone' => $payload['phone'] ?? $entry->phone,
            'plate_number' => $payload['plateNumber'] ?? $entry->plate_number,
            'dob' => $payload['dob'] ?? $entry->dob,
            'nationality' => $payload['nationality'] ?? $entry->nationality,
            'address' => $payload['address'] ?? $entry->address,
            'expires_at' => $payload['expiresAt'] ?? $entry->expires_at,
            'notes' => $payload['notes'] ?? $entry->notes,
        ])->save();

        $this->audit->record($officer, 'watchlist.updated', 'watchlist', $entry->id, 'Updated watchlist entry '.$entry->entry_number, 'done', [], $request);

        return $entry->fresh();
    }

    public function remove(WatchlistEntry $entry, Officer $officer, string $verificationCode, ?string $reason, Request $request): void
    {
        $this->verifyEditCode($officer, $verificationCode);
        $number = $entry->entry_number;
        $entry->delete();

        $this->audit->record($officer, 'watchlist.removed', 'watchlist', null, 'Removed watchlist entry '.$number, 'done', ['reason' => $reason], $request);
    }

    /**
     * @param  list<array<string, mixed>>  $entries
     * @return array<string, mixed>
     */
    public function import(array $entries, Officer $officer, Request $request): array
    {
        $created = 0;
        $skipped = 0;
        $errors = [];

        foreach ($entries as $index => $row) {
            if (empty($row['type']) || empty($row['risk']) || empty($row['reason']) || empty($row['source'])) {
                $skipped++;
                $errors[] = ['row' => $index + 1, 'message' => 'type, risk, reason and source are required'];

                continue;
            }

            $this->create($row, $officer, $request);
            $created++;
        }

        $this->audit->record($officer, 'watchlist.imported', 'watchlist', null, "Imported {$created} watchlist entries", 'done', ['skipped' => $skipped], $request);

        return ['created' => $created, 'skipped' => $skipped, 'errors' => $errors];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function subscriptions(): array
    {
        return WatchlistSubscription::query()->orderBy('agency')->get()->map(fn (WatchlistSubscription $s): array => [
            'subscriptionId' => $s->id,
            'subscriberName' => $s->subscriber_name,
            'agency' => $s->agency,
            'scope' => $s->scope,
            'modes' => $s->modes,
            'notifyEmail' => $s->notify_email,
            'status' => $s->status,
            'createdAt' => $s->created_at?->toIso8601String(),
        ])->all();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function createSubscription(array $payload, Officer $officer, Request $request): WatchlistSubscription
    {
        $subscription = WatchlistSubscription::create([
            'id' => 'sub_'.Str::lower((string) Str::ulid()),
            'subscriber_name' => $payload['subscriberName'],
            'agency' => $payload['agency'],
            'scope' => $payload['scope'],
            'modes' => $payload['modes'] ?? [],
            'notify_email' => $payload['notifyEmail'] ?? true,
            'officer_id' => $officer->id,
            'status' => 'active',
            'created_by' => $officer->id,
        ]);

        $this->audit->record($officer, 'watchlist.subscription_created', 'watchlist_subscription', $subscription->id, 'Created watchlist subscription for '.$subscription->agency, 'done', [], $request);

        return $subscription;
    }

    public function removeSubscription(WatchlistSubscription $subscription, Officer $officer, Request $request): void
    {
        $subscription->delete();
        $this->audit->record($officer, 'watchlist.subscription_removed', 'watchlist_subscription', $subscription->id, 'Removed watchlist subscription', 'done', [], $request);
    }

    private function verifyEditCode(Officer $officer, string $code): void
    {
        if (! preg_match('/^\d{6}$/', $code)) {
            throw NpisApiException::validation('Enter the 6-digit edit code.', ['verificationCode' => 'Must be 6 digits']);
        }

        if ($officer->watchlist_edit_code_hash && ! Hash::check($code, $officer->watchlist_edit_code_hash)) {
            throw NpisApiException::make(403, 'FORBIDDEN', 'The watchlist edit code is incorrect.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function contract(WatchlistEntry $entry, bool $detail = false): array
    {
        $data = [
            'watchlistEntryId' => $entry->id,
            'entryNumber' => $entry->entry_number,
            'type' => $entry->type,
            'risk' => $entry->risk,
            'reason' => $entry->reason,
            'source' => $entry->source,
            'fullName' => $entry->full_name,
            'nin' => $entry->nin,
            'phone' => $entry->phone,
            'plateNumber' => $entry->plate_number,
            'dob' => $entry->dob?->format('Y-m-d'),
            'nationality' => $entry->nationality,
            'address' => $entry->address,
            'status' => $entry->status,
            'expiresAt' => $entry->expires_at?->toIso8601String(),
            'notes' => $entry->notes,
            'createdAt' => $entry->created_at?->toIso8601String(),
        ];

        if ($detail) {
            $data['validity'] = [
                'status' => $entry->status,
                'expiresAt' => $entry->expires_at?->toIso8601String(),
                'editWindow' => '6-digit edit code required',
            ];
            $data['actions'] = ['edit', 'export', 'remove', 'open_case', 'escalate'];
        }

        return $data;
    }

    private function nextEntryNumber(): string
    {
        $sequence = WatchlistEntry::query()->count() + 1;

        do {
            $number = 'WL-26-'.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
            $sequence++;
        } while (WatchlistEntry::query()->where('entry_number', $number)->exists());

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
