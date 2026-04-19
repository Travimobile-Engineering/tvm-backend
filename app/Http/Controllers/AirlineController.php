<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateAirlineManifestRequest;
use App\Http\Requests\UpdateAirlineManifestRequest;
use App\Http\Requests\UploadAirlineManifestRequest;
use App\Models\AirlineManifest;
use App\Services\Airline\AirlineService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AirlineController extends Controller
{
    public function __construct(protected AirlineService $airlineService) {}

    public function getManifests(Request $request): JsonResponse
    {
        return $this->airlineService->getManifests($request);
    }

    public function createManifest(CreateAirlineManifestRequest $request): JsonResponse
    {
        return $this->airlineService->createManifest($request);
    }

    public function getManifest(AirlineManifest $manifest): JsonResponse
    {
        return $this->airlineService->getManifest($manifest);
    }

    public function uploadManifest(UploadAirlineManifestRequest $request): JsonResponse
    {
        return $this->airlineService->uploadManifest($request);
    }

    public function exportManifest(AirlineManifest $manifest, Request $request): BinaryFileResponse
    {
        return $this->airlineService->exportManifest($manifest, $request);
    }

    public function updateManifest(UpdateAirlineManifestRequest $request, AirlineManifest $manifest): JsonResponse
    {
        return $this->airlineService->updateManifest($request, $manifest);
    }

    public function destroyManifest(AirlineManifest $manifest): JsonResponse
    {
        return $this->airlineService->destroyManifest($manifest);
    }

    public function overview($userId)
    {
        return $this->airlineService->overview($userId);
    }

    public function topUp(Request $request)
    {
        $request->validate([
            'airline_id' => ['required', 'integer', 'exists:airlines,id'],
            'amount' => ['required', 'numeric'],
            'redirect_url' => ['required', 'string', 'url'],
        ]);

        return $this->airlineService->topUp($request);
    }
}
