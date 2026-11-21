<?php

namespace App\Http\Controllers\Api\V1\ManifestChecker\Field;

use App\Http\Controllers\Api\V1\ManifestChecker\EmfApiController;
use App\Models\Emf\Manifest;
use App\Services\Emf\Field\EmfFieldHomeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HomeController extends EmfApiController
{
    public function __construct(private readonly EmfFieldHomeService $home) {}

    public function index(Request $request): JsonResponse
    {
        return $this->success($this->home->home($this->officer($request)), 'Field home.');
    }

    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate(['q' => ['required', 'string', 'min:2', 'max:120']]);

        return $this->success($this->home->search((string) $validated['q'], $this->officer($request)), 'Manifest search.');
    }

    public function upcoming(Request $request): JsonResponse
    {
        return $this->success($this->home->upcoming($this->officer($request)), 'Upcoming manifests.');
    }

    public function upcomingManifest(Request $request, Manifest $manifest): JsonResponse
    {
        $this->guardState($manifest->state_code, $this->officer($request));

        return $this->success($this->home->upcoming($this->officer($request), $manifest->id), 'Upcoming manifest.');
    }

    public function recent(Request $request): JsonResponse
    {
        $limit = min(50, max(1, (int) $request->integer('limit', 10)));

        return $this->success($this->home->recentChecks($this->officer($request), $limit), 'Recently checked manifests.');
    }
}
