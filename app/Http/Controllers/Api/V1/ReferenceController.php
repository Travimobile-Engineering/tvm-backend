<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\NpisApiException;
use App\Services\Npis\EventService;
use App\Services\Npis\ReferenceDataService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReferenceController extends NpisApiController
{
    public function __construct(
        private readonly ReferenceDataService $reference,
        private readonly EventService $events,
    ) {}

    public function ranks(): JsonResponse
    {
        return $this->success($this->reference->ranks(), 'Ranks retrieved successfully');
    }

    public function states(): JsonResponse
    {
        return $this->success($this->reference->states(), 'States retrieved successfully');
    }

    public function commands(Request $request): JsonResponse
    {
        $stateCode = (string) $request->query('stateCode', '');

        if ($stateCode === '') {
            throw NpisApiException::validation('stateCode is required.', ['stateCode' => 'Required']);
        }

        return $this->success($this->reference->commands($stateCode), 'Commands retrieved successfully');
    }

    public function units(Request $request): JsonResponse
    {
        $commandId = (string) $request->query('commandId', '');

        if ($commandId === '') {
            throw NpisApiException::validation('commandId is required.', ['commandId' => 'Required']);
        }

        return $this->success($this->reference->units($commandId), 'Units retrieved successfully');
    }

    public function airports(): JsonResponse
    {
        return $this->success($this->reference->airports(), 'Airports retrieved successfully');
    }

    public function banks(): JsonResponse
    {
        return $this->success($this->reference->banks(), 'Banks retrieved successfully');
    }

    public function protocol(): JsonResponse
    {
        return $this->success($this->events->protocol(), 'Protocol retrieved successfully');
    }
}
