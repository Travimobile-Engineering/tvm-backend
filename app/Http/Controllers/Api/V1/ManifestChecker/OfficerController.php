<?php

namespace App\Http\Controllers\Api\V1\ManifestChecker;

use App\Models\Emf\Device;
use App\Models\Emf\Officer;
use App\Services\Emf\EmfAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OfficerController extends EmfApiController
{
    public function __construct(private readonly EmfAccessService $access) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'status' => ['sometimes', 'in:on_duty,off_duty,suspended'],
            'grant' => ['sometimes', 'in:free,active'],
            'search' => ['sometimes', 'string', 'max:120'],
            'stateCode' => ['sometimes', 'string', 'max:8'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'pageSize' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $result = $this->access->officers($filters, $this->officer($request));

        return $this->success([
            'data' => $result['data'],
            'pagination' => $result['pagination'],
        ], 'Officers and access.');
    }

    public function performance(Officer $officer): JsonResponse
    {
        return $this->success($this->access->performance($officer), 'Officer performance.');
    }

    public function suspend(Request $request, Officer $officer): JsonResponse
    {
        $validated = $request->validate(['reason' => ['required', 'string', 'max:1000']]);
        $this->access->setOfficerStatus($officer, 'suspended', $this->officer($request), (string) $validated['reason'], $request);

        return $this->success($this->access->performance($officer->fresh()), 'Officer account suspended.');
    }

    public function reactivate(Request $request, Officer $officer): JsonResponse
    {
        $this->access->setOfficerStatus($officer, 'off_duty', $this->officer($request), null, $request);

        return $this->success($this->access->performance($officer->fresh()), 'Officer account reactivated.');
    }

    public function devices(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'status' => ['sometimes', 'in:pending,approved,blocked'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'pageSize' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $result = $this->access->devices($filters);

        return $this->success([
            'data' => $result['data'],
            'pagination' => $result['pagination'],
        ], 'Device approvals.');
    }

    public function approveDevice(Request $request, Device $device): JsonResponse
    {
        $this->access->decideDevice($device, 'approved', $this->officer($request), $request);

        return $this->success(['deviceId' => $device->id, 'status' => 'approved'], 'Device approved.');
    }

    public function blockDevice(Request $request, Device $device): JsonResponse
    {
        $request->validate(['reason' => ['required', 'string', 'max:1000']]);
        $this->access->decideDevice($device, 'blocked', $this->officer($request), $request);

        return $this->success(['deviceId' => $device->id, 'status' => 'blocked'], 'Device blocked.');
    }
}
