<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Exceptions\NpisApiException;
use App\Http\Controllers\Api\V1\NpisApiController;
use App\Http\Requests\Npis\IssueCertificatesRequest;
use App\Models\Npis\Certificate;
use App\Services\Npis\CertificateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CertificateController extends NpisApiController
{
    public function __construct(private readonly CertificateService $certificates) {}

    public function store(IssueCertificatesRequest $request): JsonResponse
    {
        return $this->success(
            $this->certificates->issue($this->admin($request), $request->validated('registrationIds')),
            'Certificates processed successfully',
            201
        );
    }

    public function index(Request $request): JsonResponse
    {
        return $this->success(
            $this->certificates->list($request->query('deliveryStatus')),
            'Certificates retrieved successfully'
        );
    }

    public function retry(Request $request, string $certificateId): JsonResponse
    {
        $certificate = Certificate::query()->find($certificateId);

        if (! $certificate) {
            throw NpisApiException::make(404, 'NOT_FOUND', 'Certificate not found.');
        }

        return $this->success(
            $this->certificates->retryDelivery($this->admin($request), $certificate),
            'Certificate delivery retried successfully',
            202
        );
    }
}
