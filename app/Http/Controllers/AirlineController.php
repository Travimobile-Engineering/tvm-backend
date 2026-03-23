<?php

namespace App\Http\Controllers;

use App\Services\Airline\AirlineService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AirlineController extends Controller
{
    public function __construct(protected AirlineService $airlineService) {}

    public function getFlights(Request $request): JsonResponse
    {
        return $this->airlineService->getFlights($request);
    }

    public function getFlight(Request $request, int $id): JsonResponse
    {
        return $this->airlineService->getFlight($request, $id);
    }

    public function issueTicket(Request $request): JsonResponse
    {
        return $this->airlineService->issueTicket($request);
    }

    public function getTicket(Request $request, string $id): JsonResponse
    {
        return $this->airlineService->getTicket($request, $id);
    }
}
