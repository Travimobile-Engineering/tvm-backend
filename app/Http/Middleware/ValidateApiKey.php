<?php

namespace App\Http\Middleware;

use App\Services\Airline\ApiKeyService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateApiKey
{
    public function __construct(protected ApiKeyService $keyService) {}

    public function handle(Request $request, Closure $next, ?string $environment = null): Response
    {
        $airlineId = $request->route('airline_id') ??
            $request->query('airline_id') ??
            $request->input('airline_id');

        if (! $airlineId) {
            return $this->switchToCredentials($request, $environment);
        }

        return $next($request);
    }

    /**
     * Supports two auth styles:
     *   1) Explicit headers: X-Public-Key + X-Secret-Key
     *   2) HTTP Basic Auth: Authorization: Basic base64(publicKey:secretKey)
     */
    private function extractCredentials(Request $request): array
    {
        if ($request->hasHeader('X-Public-Key') && $request->hasHeader('X-Secret-Key')) {
            return [
                trim($request->header('X-Public-Key')),
                trim($request->header('X-Secret-Key')),
            ];
        }

        $authHeader = $request->header('Authorization', '');
        if (str_starts_with($authHeader, 'Basic ')) {
            $decoded = base64_decode(substr($authHeader, 6));
            if ($decoded && str_contains($decoded, ':')) {
                return explode(':', $decoded, 2);
            }
        }

        return [null, null];
    }

    private function unauthorized(string $message): Response
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'code' => 'INVALID_API_KEY',
        ], 401);
    }

    private function forbidden(string $message): Response
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'code' => 'WRONG_ENVIRONMENT',
        ], 403);
    }

    private function switchToCredentials($request, $environment): ?Response
    {
        [$publicKey, $secretKey] = $this->extractCredentials($request);

        if (! $publicKey || ! $secretKey) {
            return $this->unauthorized('API credentials missing. Provide X-Public-Key and X-Secret-Key headers.');
        }

        $apiKey = $this->keyService->authenticate($publicKey, $secretKey);

        if (! $apiKey) {
            return $this->unauthorized('Invalid API credentials.');
        }

        // If a specific environment is required by the route (e.g. "production")
        if ($environment && $apiKey->environment !== $environment) {
            return $this->forbidden(
                "This endpoint requires {$environment} keys. You provided {$apiKey->environment} keys."
            );
        }

        $apiKey->recordUsage($request->ip());

        $request->attributes->set('authenticated_airline', $apiKey->airline);
        $request->attributes->set('authenticated_api_key', $apiKey);
        $request->attributes->set('api_environment', $apiKey->environment);

        return null;
    }
}
