<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateApiKeyRequest;
use App\Services\Airline\AirlineService;
use App\Trait\HttpResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ApiKeyController extends Controller
{
    use HttpResponse;

    public function __construct(protected AirlineService $airlineService) {}

    public function index(Request $request): JsonResponse
    {
        return $this->airlineService->index($request);
    }

    public function generate(GenerateApiKeyRequest $request): JsonResponse
    {
        return $this->airlineService->generate($request);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        return $this->airlineService->show($request, $id);
    }

    public function rotate(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'airline_id' => ['required', 'integer', 'exists:airlines,id'],
        ]);

        return $this->airlineService->rotate($request, $id);
    }

    public function revoke(Request $request, int $id): JsonResponse
    {
        return $this->airlineService->revoke($request, $id);
    }

    public function showCurrentEnvironment(Request $request): JsonResponse
    {
        return $this->airlineService->showCurrentEnvironment($request);
    }

    public function toggleEnvironment(Request $request): JsonResponse
    {
        $request->validate([
            'airline_id' => ['required', 'integer', 'exists:airlines,id'],
            'environment' => ['required', Rule::in(['test', 'production'])],
        ]);

        return $this->airlineService->toggleEnvironment($request);
    }

    public function getAudits(Request $request): JsonResponse
    {
        $request->validate([
            'event' => ['nullable', 'string'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
        ]);

        return $this->airlineService->getAudits($request);
    }
}
