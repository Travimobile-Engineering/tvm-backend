<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\NpisApiController;
use App\Http\Requests\Npis\UpdateEventRequest;
use App\Services\Npis\EventService;
use Illuminate\Http\JsonResponse;

class EventController extends NpisApiController
{
    public function __construct(private readonly EventService $events) {}

    public function settings(): JsonResponse
    {
        return $this->success($this->events->settings(), 'Event settings retrieved successfully');
    }

    public function update(UpdateEventRequest $request): JsonResponse
    {
        $event = $this->events->update(
            $request->validated(),
            $this->admin($request),
            $request->file('brochureFile'),
        );

        return $this->success($this->events->eventContract($event), 'Event settings updated successfully');
    }
}
