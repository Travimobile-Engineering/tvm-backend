<?php

namespace App\Http\Controllers\Api\V1\ManifestChecker;

use App\Http\Requests\Emf\IncidentAssignRequest;
use App\Http\Requests\Emf\IncidentConvertRequest;
use App\Http\Requests\Emf\IncidentResolveRequest;
use App\Http\Requests\Emf\IncidentStoreRequest;
use App\Http\Requests\Emf\ManifestNoteRequest;
use App\Models\Emf\Incident;
use App\Services\Emf\EmfIncidentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IncidentController extends EmfApiController
{
    public function __construct(private readonly EmfIncidentService $incidents) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'severity' => ['sometimes', 'in:critical,high,medium,low'],
            'status' => ['sometimes', 'in:unsig,assigned,acknowledged,in_progress,resolved'],
            'search' => ['sometimes', 'string', 'max:120'],
            'stateCode' => ['sometimes', 'string', 'max:8'],
            'period' => ['sometimes', 'in:24h,7d,30d'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'pageSize' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $result = $this->incidents->paginate($filters, $this->officer($request));

        return $this->success([
            'data' => $result['data'],
            'pagination' => $result['pagination'],
        ], 'Incidents.');
    }

    public function store(IncidentStoreRequest $request): JsonResponse
    {
        $incident = $this->incidents->create($request->validated(), $this->officer($request), $request);

        return $this->success($this->incidents->show($incident), 'Incident reported.', 201);
    }

    public function show(Request $request, Incident $incident): JsonResponse
    {
        $this->guardState($incident->state_code, $this->officer($request));

        return $this->success($this->incidents->show($incident), 'Incident detail.');
    }

    public function assign(IncidentAssignRequest $request, Incident $incident): JsonResponse
    {
        $officer = $this->officer($request);
        $this->guardState($incident->state_code, $officer);
        $this->incidents->assign($incident, $request->validated(), $officer, $request);

        return $this->success($this->incidents->show($incident->fresh()), 'Responder assigned to incident.');
    }

    public function convert(IncidentConvertRequest $request, Incident $incident): JsonResponse
    {
        $officer = $this->officer($request);
        $this->guardState($incident->state_code, $officer);
        $case = $this->incidents->convertToCase($incident, $request->validated(), $officer, $request);

        return $this->success($case, 'Incident converted to a case.', 201);
    }

    public function addNote(ManifestNoteRequest $request, Incident $incident): JsonResponse
    {
        $officer = $this->officer($request);
        $this->guardState($incident->state_code, $officer);
        $this->incidents->addNote($incident, (string) $request->string('note'), $officer, $request);

        return $this->success($this->incidents->show($incident->fresh()), 'Note added to incident.');
    }

    public function resolve(IncidentResolveRequest $request, Incident $incident): JsonResponse
    {
        $officer = $this->officer($request);
        $this->guardState($incident->state_code, $officer);
        $this->incidents->resolve($incident, (string) $request->string('note'), $officer, $request);

        return $this->success($this->incidents->show($incident->fresh()), 'Incident resolved.');
    }
}
