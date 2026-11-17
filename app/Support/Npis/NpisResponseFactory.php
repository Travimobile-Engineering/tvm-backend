<?php

namespace App\Support\Npis;

use App\Trait\HttpResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class NpisResponseFactory
{
    use HttpResponse;

    /**
     * Build the standard error envelope through the shared HttpResponse trait.
     *
     * @param  array<string, mixed>  $details
     */
    public function apiError(
        Request $request,
        int $status,
        string $code,
        string $message,
        array $details = [],
        ?int $retryAfter = null,
    ): JsonResponse {
        $requestId = $request->attributes->get('npis_request_id') ?? (string) Str::uuid();

        $data = array_filter([
            'code' => $code,
            'details' => $details === [] ? null : $details,
            'requestId' => $requestId,
            'retryAfterSeconds' => $retryAfter,
        ], static fn (mixed $value): bool => $value !== null);

        $response = $this->error($data, $message, $status);
        $response->header('X-Request-Id', $requestId);

        if ($retryAfter !== null) {
            $response->header('Retry-After', (string) $retryAfter);
        }

        return $response;
    }
}
