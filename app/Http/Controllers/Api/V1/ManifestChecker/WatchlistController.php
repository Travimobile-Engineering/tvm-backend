<?php

namespace App\Http\Controllers\Api\V1\ManifestChecker;

use App\Http\Requests\Emf\WatchlistImportRequest;
use App\Http\Requests\Emf\WatchlistRemoveRequest;
use App\Http\Requests\Emf\WatchlistStoreRequest;
use App\Http\Requests\Emf\WatchlistSubscriptionRequest;
use App\Http\Requests\Emf\WatchlistUpdateRequest;
use App\Models\Emf\WatchlistDocument;
use App\Models\Emf\WatchlistEntry;
use App\Models\Emf\WatchlistSubscription;
use App\Services\Emf\EmfAuditService;
use App\Services\Emf\EmfCaseService;
use App\Services\Emf\EmfIncidentService;
use App\Services\Emf\EmfWatchlistService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WatchlistController extends EmfApiController
{
    public function __construct(
        private readonly EmfWatchlistService $watchlist,
        private readonly EmfAuditService $audit,
        private readonly EmfIncidentService $incidents,
        private readonly EmfCaseService $cases,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'type' => ['sometimes', 'in:person,vehicle,phone,organization'],
            'risk' => ['sometimes', 'in:critical,high,medium,low'],
            'status' => ['sometimes', 'in:active,cleared,expired'],
            'search' => ['sometimes', 'string', 'max:120'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'pageSize' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $result = $this->watchlist->paginate($filters);

        return $this->success([
            'data' => $result['data'],
            'pagination' => $result['pagination'],
        ], 'Watchlist entries.');
    }

    public function store(WatchlistStoreRequest $request): JsonResponse
    {
        $entry = $this->watchlist->create($request->validated(), $this->officer($request), $request);

        return $this->success($this->watchlist->show($entry), 'Watchlist entry created.', 201);
    }

    public function show(WatchlistEntry $entry): JsonResponse
    {
        return $this->success($this->watchlist->show($entry), 'Watchlist entry detail.');
    }

    public function update(WatchlistUpdateRequest $request, WatchlistEntry $entry): JsonResponse
    {
        $entry = $this->watchlist->update($entry, $request->validated(), $this->officer($request), $request);

        return $this->success($this->watchlist->show($entry), 'Watchlist entry updated.');
    }

    public function destroy(WatchlistRemoveRequest $request, WatchlistEntry $entry): JsonResponse
    {
        $this->watchlist->remove(
            $entry,
            $this->officer($request),
            (string) $request->string('verificationCode'),
            $request->input('reason'),
            $request,
        );

        return $this->success(null, 'Watchlist entry removed.');
    }

    public function import(WatchlistImportRequest $request): JsonResponse
    {
        $result = $this->watchlist->import(
            (array) $request->input('entries', []),
            $this->officer($request),
            $request,
        );

        return $this->success($result, 'Watchlist import completed.');
    }

    public function escalate(Request $request, WatchlistEntry $entry): JsonResponse
    {
        $validated = $request->validate([
            'severity' => ['required', 'in:critical,high,medium,low'],
            'location' => ['sometimes', 'string', 'max:200'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'assigneeId' => ['sometimes', 'nullable', 'string', 'exists:emf_officers,id'],
        ]);

        $incident = $this->incidents->create([
            'title' => 'Watchlist escalation — '.($entry->full_name ?? $entry->entry_number),
            'type' => 'watchlist_match',
            'severity' => $validated['severity'],
            'summary' => $validated['notes'] ?? $entry->reason,
            'watchlistEntryId' => $entry->id,
            'location' => $validated['location'] ?? 'Command operations room',
            'assigneeId' => $validated['assigneeId'] ?? null,
            'source' => 'watchlist',
        ], $this->officer($request), $request);

        return $this->success($this->incidents->show($incident), 'Watchlist entry escalated to an incident.', 201);
    }

    public function openCase(Request $request, WatchlistEntry $entry): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:200'],
            'severity' => ['required', 'in:critical,high,medium,low'],
            'ownerId' => ['sometimes', 'nullable', 'string', 'exists:emf_officers,id'],
            'dueDate' => ['sometimes', 'nullable', 'date', 'after:today'],
            'summary' => ['sometimes', 'nullable', 'string', 'max:4000'],
        ]);

        $case = $this->cases->create([
            'title' => $validated['title'] ?? 'Watchlist case — '.($entry->full_name ?? $entry->entry_number),
            'severity' => $validated['severity'],
            'ownerId' => $validated['ownerId'] ?? $this->officer($request)->id,
            'dueDate' => $validated['dueDate'] ?? null,
            'summary' => $validated['summary'] ?? $entry->reason,
            'linkedRecords' => [['type' => 'watchlistEntry', 'id' => $entry->id, 'number' => $entry->entry_number]],
        ], $this->officer($request), $request);

        return $this->success($this->cases->show($case), 'Case opened from watchlist entry.', 201);
    }

    public function subscriptions(): JsonResponse
    {
        return $this->success($this->watchlist->subscriptions(), 'Watchlist subscriptions and assignments.');
    }

    public function storeSubscription(WatchlistSubscriptionRequest $request): JsonResponse
    {
        $this->watchlist->createSubscription($request->validated(), $this->officer($request), $request);

        return $this->success($this->watchlist->subscriptions(), 'Subscription created.', 201);
    }

    public function destroySubscription(Request $request, WatchlistSubscription $subscription): JsonResponse
    {
        $this->watchlist->removeSubscription($subscription, $this->officer($request), $request);

        return $this->success(null, 'Subscription removed.');
    }

    public function documents(Request $request): JsonResponse
    {
        $query = WatchlistDocument::query()->with('entry')->orderByDesc('created_at');
        $page = max(1, (int) $request->integer('page', 1));
        $pageSize = min(100, max(1, (int) $request->integer('pageSize', 25)));
        $total = (clone $query)->count();

        return $this->success([
            'data' => $query->offset(($page - 1) * $pageSize)->limit($pageSize)->get()->map(fn (WatchlistDocument $document): array => [
                'documentId' => $document->id,
                'entryNumber' => $document->entry?->entry_number,
                'entryName' => $document->entry?->full_name,
                'name' => $document->name,
                'fileUrl' => $document->file_url,
                'fileSize' => $document->file_size,
                'createdAt' => $document->created_at?->toIso8601String(),
            ])->all(),
            'pagination' => $this->pagination($page, $pageSize, $total),
        ], 'Watchlist documents.');
    }

    public function storeDocument(Request $request, WatchlistEntry $entry): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'file' => ['nullable', 'file', 'max:10240'],
            'fileUrl' => ['nullable', 'url', 'max:2048'],
        ]);

        $fileUrl = $validated['fileUrl'] ?? null;
        $fileSize = null;

        if ($request->hasFile('file')) {
            $upload = uploadFile($request, 'file', 'emf/watchlist');
            $fileUrl = $upload['url'];
            $fileSize = $request->file('file')?->getSize();
        }

        if (! $fileUrl) {
            return $this->error(['code' => 'VALIDATION_FAILED', 'details' => ['file' => 'Upload a file or provide fileUrl.']], 'Some details need attention.', 422);
        }

        $document = WatchlistDocument::create([
            'entry_id' => $entry->id,
            'name' => $validated['name'],
            'file_url' => $fileUrl,
            'file_size' => $fileSize,
            'uploaded_by' => $this->officer($request)->id,
        ]);

        $this->audit->record($this->officer($request), 'watchlist.document_added', 'watchlist', $entry->id, 'Added document '.$document->name, 'done', [], $request);

        return $this->success($this->watchlist->show($entry), 'Document added.', 201);
    }

    public function destroyDocument(Request $request, WatchlistDocument $document): JsonResponse
    {
        $entry = $document->entry;
        $name = $document->name;
        $document->delete();
        $this->audit->record($this->officer($request), 'watchlist.document_removed', 'watchlist', $entry?->id, 'Removed document '.$name, 'done', [], $request);

        return $this->success(null, 'Document removed.');
    }
}
