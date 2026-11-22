<?php

namespace App\Http\Controllers\Api\V1\ManifestChecker;

use App\Http\Requests\Emf\AnalyticsExportRequest;
use App\Http\Requests\Emf\ScheduleStoreRequest;
use App\Models\Emf\DutyPoint;
use App\Services\Emf\EmfAnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnalyticsController extends EmfApiController
{
    public function __construct(private readonly EmfAnalyticsService $analytics) {}

    public function overview(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'period' => ['sometimes', 'in:24h,7d,30d,quarter'],
            'stateCode' => ['sometimes', 'string', 'max:8'],
            'mode' => ['sometimes', 'in:road,sea,air,rail,hospitality'],
        ]);

        return $this->success($this->analytics->overview($filters, $this->officer($request)), 'Analytics overview.');
    }

    public function unit(Request $request, DutyPoint $dutyPoint): JsonResponse
    {
        $filters = $request->validate(['period' => ['sometimes', 'in:24h,7d,30d,quarter']]);

        return $this->success($this->analytics->unit($dutyPoint, $filters), 'Unit drill-down.');
    }

    public function coverage(Request $request): JsonResponse
    {
        $validated = $request->validate(['stateCode' => ['sometimes', 'string', 'max:8']]);

        return $this->success(
            $this->analytics->coverageGaps((string) ($validated['stateCode'] ?? $this->officer($request)->state_code)),
            'Coverage gaps.'
        );
    }

    public function export(AnalyticsExportRequest $request): JsonResponse
    {
        $filters = $request->safe()->except(['code', 'watermark']);

        return $this->success($this->analytics->export(
            $filters,
            $this->officer($request),
            $request,
            (string) $request->string('code'),
            (bool) $request->boolean('watermark'),
        ), 'Analytics export ready.');
    }

    public function schedules(): JsonResponse
    {
        return $this->success($this->analytics->schedules(), 'Scheduled reports.');
    }

    public function storeSchedule(ScheduleStoreRequest $request): JsonResponse
    {
        $this->analytics->createSchedule($request->validated(), $this->officer($request), $request);

        return $this->success($this->analytics->schedules(), 'Report schedule created.', 201);
    }
}
