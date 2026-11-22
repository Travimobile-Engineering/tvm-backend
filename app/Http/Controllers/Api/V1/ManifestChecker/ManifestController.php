<?php

namespace App\Http\Controllers\Api\V1\ManifestChecker;

use App\Http\Requests\Emf\ManifestCheckRequest;
use App\Http\Requests\Emf\ManifestNoteRequest;
use App\Models\Emf\Manifest;
use App\Services\Emf\EmfExportService;
use App\Services\Emf\EmfManifestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ManifestController extends EmfApiController
{
    public function __construct(
        private readonly EmfManifestService $manifests,
        private readonly EmfExportService $exports,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search' => ['sometimes', 'string', 'max:120'],
            'mode' => ['sometimes', 'in:road,sea,air,rail,hospitality'],
            'verdict' => ['sometimes', 'in:clear,flagged,mismatch,unverified'],
            'nodeId' => ['sometimes', 'string', 'max:64'],
            'stateCode' => ['sometimes', 'string', 'max:8'],
            'lgaId' => ['sometimes', 'string', 'max:64'],
            'period' => ['sometimes', 'in:24h,7d,30d'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'pageSize' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $result = $this->manifests->paginate($filters, $this->officer($request));

        return $this->success([
            'data' => $result['data'],
            'pagination' => $result['pagination'],
        ], 'Manifest checks.');
    }

    public function store(ManifestCheckRequest $request): JsonResponse
    {
        $manifest = $this->manifests->createCheck($request->validated(), $this->officer($request), $request);

        return $this->success($this->manifests->show($manifest), 'Manifest checked.', 201);
    }

    public function show(Request $request, Manifest $manifest): JsonResponse
    {
        $this->guardState($manifest->state_code, $this->officer($request));

        return $this->success($this->manifests->show($manifest), 'Manifest detail.');
    }

    public function passengers(Request $request, Manifest $manifest): JsonResponse
    {
        $this->guardState($manifest->state_code, $this->officer($request));
        $page = max(1, (int) $request->integer('page', 1));
        $pageSize = min(100, max(1, (int) $request->integer('pageSize', 25)));
        $result = $this->manifests->passengers($manifest, $page, $pageSize);

        return $this->success([
            'data' => $result['data'],
            'pagination' => $result['pagination'],
        ], 'Manifest passengers.');
    }

    public function addNote(ManifestNoteRequest $request, Manifest $manifest): JsonResponse
    {
        $officer = $this->officer($request);
        $this->guardState($manifest->state_code, $officer);
        $manifest = $this->manifests->addNote($manifest, $officer, (string) $request->string('note'), $request);

        return $this->success($this->manifests->show($manifest), 'Note added to manifest.');
    }

    public function export(Request $request, Manifest $manifest): JsonResponse
    {
        $officer = $this->officer($request);
        $this->guardState($manifest->state_code, $officer);

        return $this->success($this->exports->create(
            'manifests',
            ['manifestNumber' => $manifest->manifest_number],
            $officer,
            $request,
        ), 'Manifest export ready.');
    }
}
