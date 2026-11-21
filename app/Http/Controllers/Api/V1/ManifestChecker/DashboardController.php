<?php

namespace App\Http\Controllers\Api\V1\ManifestChecker;

use App\Services\Emf\EmfDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends EmfApiController
{
    public function __construct(private readonly EmfDashboardService $dashboard) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'stateCode' => ['sometimes', 'string', 'max:8'],
            'lgaId' => ['sometimes', 'string', 'max:64'],
            'mode' => ['sometimes', 'in:road,sea,air,rail,hospitality'],
            'period' => ['sometimes', 'in:24h,7d,30d,quarter'],
        ]);

        return $this->success(
            $this->dashboard->overview($this->officer($request), $filters),
            'Command dashboard.'
        );
    }

    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:120'],
        ]);

        return $this->success(
            $this->dashboard->search((string) $validated['q'], $this->officer($request)),
            'Search results.'
        );
    }
}
