<?php

namespace App\Http\Controllers\Api\V1\ManifestChecker\Field;

use App\Http\Controllers\Api\V1\ManifestChecker\EmfApiController;
use App\Models\Emf\Alert;
use App\Services\Emf\Field\EmfFieldAlertService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AlertController extends EmfApiController
{
    public function __construct(private readonly EmfFieldAlertService $alerts) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'tab' => ['sometimes', 'in:active,resolved'],
            'type' => ['sometimes', 'in:critical,flagged,mismatch,unverified'],
            'dutyPointId' => ['sometimes', 'string', 'max:64'],
            'search' => ['sometimes', 'string', 'max:120'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'pageSize' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        return $this->success($this->alerts->paginate($this->officer($request), $filters), 'Field alerts.');
    }

    public function show(Request $request, Alert $alert): JsonResponse
    {
        $this->guardState($alert->state_code, $this->officer($request));

        return $this->success($this->alerts->show($alert), 'Field alert detail.');
    }

    public function acknowledge(Request $request, Alert $alert): JsonResponse
    {
        $officer = $this->officer($request);
        $this->guardState($alert->state_code, $officer);
        $validated = $request->validate(['notifyPost' => ['sometimes', 'boolean']]);

        return $this->success($this->alerts->acknowledge($alert, $officer, (bool) ($validated['notifyPost'] ?? false), $request), 'Alert acknowledged.');
    }

    public function clear(Request $request, Alert $alert): JsonResponse
    {
        $officer = $this->officer($request);
        $this->guardState($alert->state_code, $officer);
        $validated = $request->validate([
            'outcome' => ['required', 'in:cleared,interrupted,withdrawn'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        return $this->success($this->alerts->clear($alert, $officer, (string) $validated['outcome'], $validated['note'] ?? null, $request), 'Alert cleared.');
    }
}
