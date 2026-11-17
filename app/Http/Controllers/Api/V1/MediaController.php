<?php

namespace App\Http\Controllers\Api\V1;

use App\Contracts\NpisCloudStorage;
use App\Exceptions\NpisApiException;
use App\Models\Npis\Certificate;
use App\Models\Npis\ExportJob;
use App\Models\Npis\Registration;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class MediaController extends NpisApiController
{
    public function __construct(private readonly NpisCloudStorage $cloud) {}

    public function photo(Request $request, string $registration): Response
    {
        if (! $request->hasValidSignature()) {
            throw NpisApiException::make(403, 'FORBIDDEN', 'This photo link has expired.');
        }

        $registration = Registration::query()->with('badge')->find($registration);
        $url = $registration?->badge?->photo_url;

        if (! $url) {
            throw NpisApiException::make(404, 'NOT_FOUND', 'Photo not found.');
        }

        $body = $this->cloud->fetch($url);

        if ($body === null) {
            throw NpisApiException::make(404, 'NOT_FOUND', 'Photo not found.');
        }

        return response($body, 200, [
            'Content-Type' => 'image/jpeg',
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function certificate(Request $request, string $certificate): Response
    {
        if (! $request->hasValidSignature()) {
            throw NpisApiException::make(403, 'FORBIDDEN', 'This certificate link has expired.');
        }

        $certificate = Certificate::query()->find($certificate);
        $url = $certificate?->file_url;

        if (! $certificate || ! $url) {
            throw NpisApiException::make(404, 'NOT_FOUND', 'The certificate file is still being generated.');
        }

        $body = $this->cloud->fetch($url);

        if ($body === null) {
            throw NpisApiException::make(404, 'NOT_FOUND', 'The certificate file is still being generated.');
        }

        return response($body, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$certificate->certificate_number.'.pdf"',
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
    }

    public function export(Request $request, string $export): Response
    {
        if (! $request->hasValidSignature()) {
            throw NpisApiException::make(403, 'FORBIDDEN', 'This export link has expired.');
        }

        $job = ExportJob::query()->find($export);
        $url = $job?->file_url;

        if (! $job || ! $url) {
            throw NpisApiException::make(404, 'NOT_FOUND', 'Export file not found.');
        }

        $body = $this->cloud->fetch($url);

        if ($body === null) {
            throw NpisApiException::make(404, 'NOT_FOUND', 'Export file not found.');
        }

        return response($body, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="npis-registrations-'.$job->id.'.csv"',
        ]);
    }
}
