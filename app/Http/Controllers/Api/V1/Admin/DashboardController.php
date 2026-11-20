<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\NpisApiController;
use App\Services\Npis\OrganizerDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends NpisApiController
{
    public function __construct(private readonly OrganizerDashboardService $dashboard) {}

    public function index(Request $request): JsonResponse
    {
        return $this->success(
            $this->dashboard->dashboard($this->admin($request)),
            'Organizer dashboard retrieved successfully'
        );
    }
}
