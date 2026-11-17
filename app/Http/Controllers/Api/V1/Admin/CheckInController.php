<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\NpisApiController;
use App\Http\Requests\Npis\CheckInRequest;
use App\Services\Npis\CheckInService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CheckInController extends NpisApiController
{
    public function __construct(private readonly CheckInService $checkIns) {}

    public function store(CheckInRequest $request): JsonResponse
    {
        $result = $this->checkIns->record(
            $this->admin($request),
            (string) $request->validated('summitTagNumber'),
            $request->validated('eventDate'),
            (string) ($request->validated('method') ?? 'tag_number'),
        );

        return $this->success(
            $result,
            $result['replayed'] ? 'Check-in already recorded for this summit day' : 'Check-in recorded successfully',
            $result['replayed'] ? 200 : 201
        );
    }

    public function index(Request $request): JsonResponse
    {
        return $this->success(
            $this->checkIns->latest($request->query('eventDate')),
            'Check-ins retrieved successfully'
        );
    }
}
