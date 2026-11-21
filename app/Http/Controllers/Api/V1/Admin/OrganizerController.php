<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\NpisApiController;
use App\Http\Requests\Npis\StoreOrganizerRequest;
use App\Services\Npis\OrganizerManagementService;
use Illuminate\Http\JsonResponse;

class OrganizerController extends NpisApiController
{
    public function __construct(private readonly OrganizerManagementService $organizers) {}

    public function options(): JsonResponse
    {
        return $this->success(
            $this->organizers->options(),
            'Organizer options retrieved successfully'
        );
    }

    public function store(StoreOrganizerRequest $request): JsonResponse
    {
        $organizer = $this->organizers->create($this->admin($request), $request->validated());

        $this->organizers->sendCredentials($organizer, (string) $request->validated('password'));

        return $this->success([
            ...$this->organizers->contract($organizer),
            'credentialsEmailed' => true,
        ], 'Organizer created successfully', 201);
    }
}
