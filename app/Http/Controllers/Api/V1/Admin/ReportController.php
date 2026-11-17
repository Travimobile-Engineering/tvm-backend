<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\NpisApiController;
use App\Http\Requests\Npis\RegistrationIndexRequest;
use App\Services\Npis\ReportService;
use Illuminate\Http\JsonResponse;

class ReportController extends NpisApiController
{
    public function __construct(private readonly ReportService $reports) {}

    public function registration(RegistrationIndexRequest $request): JsonResponse
    {
        return $this->success(
            $this->reports->registration($request->validated(), $this->admin($request)),
            'Registration report retrieved successfully'
        );
    }

    public function travel(RegistrationIndexRequest $request): JsonResponse
    {
        return $this->success(
            $this->reports->travel($request->validated(), $this->admin($request)),
            'Travel report retrieved successfully'
        );
    }

    public function accommodation(RegistrationIndexRequest $request): JsonResponse
    {
        return $this->success(
            $this->reports->accommodation($request->validated(), $this->admin($request)),
            'Accommodation report retrieved successfully'
        );
    }

    public function training(RegistrationIndexRequest $request): JsonResponse
    {
        return $this->success(
            $this->reports->training($request->validated(), $this->admin($request)),
            'Training report retrieved successfully'
        );
    }

    public function attendance(RegistrationIndexRequest $request): JsonResponse
    {
        return $this->success(
            $this->reports->attendance($request->validated(), $this->admin($request)),
            'Attendance report retrieved successfully'
        );
    }
}
