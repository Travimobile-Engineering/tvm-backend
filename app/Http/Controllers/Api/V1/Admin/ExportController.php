<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Exceptions\NpisApiException;
use App\Http\Controllers\Api\V1\NpisApiController;
use App\Http\Requests\Npis\ExportRequest;
use App\Models\Npis\ExportJob;
use App\Services\Npis\ExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExportController extends NpisApiController
{
    public function __construct(private readonly ExportService $exports) {}

    public function store(ExportRequest $request): JsonResponse
    {
        return $this->success(
            $this->exports->create(
                $this->admin($request),
                (array) ($request->validated('filters') ?? []),
                $request->validated('fields'),
            ),
            'Export created successfully',
            202
        );
    }

    public function show(Request $request, string $exportId): JsonResponse
    {
        $export = ExportJob::query()->find($exportId);

        if (! $export) {
            throw NpisApiException::make(404, 'NOT_FOUND', 'Export not found.');
        }

        return $this->success(
            $this->exports->show($export, $this->admin($request)),
            'Export retrieved successfully'
        );
    }
}
