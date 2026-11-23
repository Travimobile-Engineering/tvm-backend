<?php

namespace App\Http\Controllers\Api\V1\ManifestChecker;

use App\Http\Requests\Emf\GrantConflictRequest;
use App\Http\Requests\Emf\GrantRevokeRequest;
use App\Http\Requests\Emf\GrantStoreRequest;
use App\Http\Requests\Emf\TemplateStoreRequest;
use App\Models\Emf\AccessGrant;
use App\Models\Emf\GrantTemplate;
use App\Services\Emf\EmfAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccessGrantController extends EmfApiController
{
    public function __construct(private readonly EmfAccessService $access) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'status' => ['sometimes', 'in:active,expired,revoked'],
            'search' => ['sometimes', 'string', 'max:120'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'pageSize' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $result = $this->access->grants($filters, $this->officer($request));

        return $this->success([
            'data' => $result['data'],
            'pagination' => $result['pagination'],
        ], 'Access grants.');
    }

    public function store(GrantStoreRequest $request): JsonResponse
    {
        $grant = $this->access->createGrant($request->validated(), $this->officer($request), $request);

        return $this->success($grant, 'Access grant created.', 201);
    }

    public function show(AccessGrant $grant): JsonResponse
    {
        return $this->success($this->access->showGrant($grant), 'Access grant detail.');
    }

    public function revoke(GrantRevokeRequest $request, AccessGrant $grant): JsonResponse
    {
        $grant = $this->access->revokeGrant(
            $grant,
            (string) $request->string('code'),
            $request->input('reason'),
            $this->officer($request),
            $request,
        );

        return $this->success($this->access->showGrant($grant), 'Access grant revoked.');
    }

    public function conflicts(GrantConflictRequest $request): JsonResponse
    {
        $conflicts = $this->access->conflictPreview(
            (array) $request->input('officerIds', []),
            (string) $request->string('startsAt'),
            (string) $request->string('endsAt'),
            (array) $request->input('modes', []),
        );

        return $this->success([
            'hasConflicts' => $conflicts !== [],
            'conflicts' => $conflicts,
        ], 'Grant conflict check.');
    }

    public function templates(): JsonResponse
    {
        return $this->success($this->access->templates(), 'Grant templates.');
    }

    public function storeTemplate(TemplateStoreRequest $request): JsonResponse
    {
        $this->access->createTemplate($request->validated(), $this->officer($request), $request);

        return $this->success($this->access->templates(), 'Grant template created.', 201);
    }

    public function updateTemplate(Request $request, GrantTemplate $template): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:160'],
            'description' => ['sometimes', 'nullable', 'string', 'max:500'],
            'scope' => ['sometimes', 'string', 'max:160'],
            'modes' => ['sometimes', 'array', 'min:1'],
            'modes.*' => ['in:road,sea,air,rail,hospitality'],
            'durationHours' => ['sometimes', 'integer', 'min:1', 'max:720'],
            'accessCount' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $this->access->updateTemplate($template, $validated, $this->officer($request), $request);

        return $this->success($this->access->templates(), 'Grant template updated.');
    }

    public function destroyTemplate(Request $request, GrantTemplate $template): JsonResponse
    {
        $this->access->deleteTemplate($template, $this->officer($request), $request);

        return $this->success($this->access->templates(), 'Grant template deleted.');
    }
}
