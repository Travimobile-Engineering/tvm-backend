<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\NpisApiController;
use App\Services\Npis\RegistrationAdminService;
use Illuminate\Http\JsonResponse;

class AccommodationController extends NpisApiController
{
    public function __construct(private readonly RegistrationAdminService $registrations) {}

    public function index(): JsonResponse
    {
        return $this->success(
            $this->registrations->hotels(),
            'Accommodation partners retrieved successfully'
        );
    }
}
