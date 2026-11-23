<?php

namespace App\Http\Controllers\Api\V1\ManifestChecker;

use App\Http\Requests\Emf\AlertAssignRequest;
use App\Http\Requests\Emf\AlertDismissRequest;
use App\Http\Requests\Emf\AlertEscalateRequest;
use App\Models\Emf\Alert;
use App\Services\Emf\EmfAlertService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AlertController extends EmfApiController
{
    public function __construct(private readonly EmfAlertService $alerts) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'type' => ['sometimes', 'in:critical,flagged,mismatch,unverified'],
            'status' => ['sometimes', 'in:open,acknowledged,assigned,escalated,dismissed,closed'],
            'mode' => ['sometimes', 'in:road,sea,air,rail,hospitality'],
            'stateCode' => ['sometimes', 'string', 'max:8'],
            'lgaId' => ['sometimes', 'string', 'max:64'],
            'period' => ['sometimes', 'in:24h,7d,30d'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'pageSize' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $result = $this->alerts->paginate($filters, $this->officer($request));

        return $this->success([
            'data' => $result['data'],
            'pagination' => $result['pagination'],
        ], 'Alert queue.');
    }

    public function show(Request $request, Alert $alert): JsonResponse
    {
        $this->guardState($alert->state_code, $this->officer($request));

        return $this->success($this->alerts->show($alert), 'Alert detail.');
    }

    public function acknowledge(Request $request, Alert $alert): JsonResponse
    {
        $officer = $this->officer($request);
        $this->guardState($alert->state_code, $officer);
        $this->alerts->acknowledge($alert, $officer, $request);

        return $this->success($this->alerts->show($alert->fresh()), 'Alert acknowledged.');
    }

    public function dismiss(AlertDismissRequest $request, Alert $alert): JsonResponse
    {
        $officer = $this->officer($request);
        $this->guardState($alert->state_code, $officer);
        $this->alerts->dismiss(
            $alert,
            $officer,
            (string) $request->string('reason'),
            $request->input('note'),
            $request,
        );

        return $this->success($this->alerts->show($alert->fresh()), 'Alert dismissed as a false positive.');
    }

    public function escalate(AlertEscalateRequest $request, Alert $alert): JsonResponse
    {
        $officer = $this->officer($request);
        $this->guardState($alert->state_code, $officer);
        $this->alerts->escalate($alert, $officer, $request->validated(), $request);

        return $this->success($this->alerts->show($alert->fresh()), 'Alert escalated to an incident.');
    }

    public function assign(AlertAssignRequest $request, Alert $alert): JsonResponse
    {
        $officer = $this->officer($request);
        $this->guardState($alert->state_code, $officer);
        $this->alerts->assign($alert, $officer, $request->validated(), $request);

        return $this->success($this->alerts->show($alert->fresh()), 'Responder assigned.');
    }
}
