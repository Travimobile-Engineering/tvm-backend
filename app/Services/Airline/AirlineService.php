<?php

namespace App\Services\Airline;

use App\Http\Resources\ApiKeyResource;
use App\Http\Resources\AuditLogResource;
use App\Models\AirlineAuditLog;
use App\Trait\HttpResponse;
use Illuminate\Http\JsonResponse;

class AirlineService
{
    use HttpResponse;

    public function __construct(
        protected ApiKeyService $keyService,
        protected AuditService $auditService
    ) {}

    public function index($request): JsonResponse
    {
        $keys = $request->user()
            ->apiKeys()
            ->withTrashed(false)
            ->when($request->environment, fn ($q) => $q->forEnvironment($request->environment))
            ->latest()
            ->get();

        return $this->success([
            'keys' => ApiKeyResource::collection($keys),
        ], 'API keys retrieved.');
    }

    public function generate($request): JsonResponse
    {
        $airline = $request->user();
        $environment = $request->input('environment', $airline->active_environment);
        $name = $request->input('name', ucfirst($environment).' Key');

        ['key' => $key, 'raw_secret' => $rawSecret] = $this->keyService->generate(
            $airline,
            $environment,
            $name
        );

        $data = [
            'key' => [
                'id' => $key->id,
                'name' => $key->name,
                'environment' => $key->environment,
                'public_key' => $key->public_key,
                'secret_key' => $rawSecret,
                'created_at' => $key->created_at,
            ],
        ];

        return $this->success($data, 'API key generated. Save the secret key — it will NOT be shown again.', 201);
    }

    public function show($request, int $id): JsonResponse
    {
        $key = $request->user()->apiKeys()->findOrFail($id);

        return $this->success([
            'key' => new ApiKeyResource($key),
        ], 'API key details.');
    }

    public function rotate($request, int $id): JsonResponse
    {
        $oldKey = $request->user()->apiKeys()->active()->findOrFail($id);

        ['key' => $newKey, 'raw_secret' => $rawSecret] = $this->keyService->rotate($oldKey);

        $data = [
            'key' => [
                'id' => $newKey->id,
                'name' => $newKey->name,
                'environment' => $newKey->environment,
                'public_key' => $newKey->public_key,
                'secret_key' => $rawSecret,
                'created_at' => $newKey->created_at,
            ],
        ];

        return $this->success($data, 'Key rotated. Save the new secret key — it will NOT be shown again.');
    }

    public function revoke($request, int $id): JsonResponse
    {
        $key = $request->user()->apiKeys()->active()->findOrFail($id);

        $this->keyService->revoke($key);

        return $this->success(null, 'API key revoked successfully.');
    }

    public function getFlights($request): JsonResponse
    {
        $airline = $request->attributes->get('authenticated_airline');
        $environment = $request->attributes->get('api_environment');

        return $this->success([
            'environment' => $environment,
            'airline' => $airline->name,
            'flights' => [],
        ], 'Flights retrieved.');
    }

    public function getFlight($request, int $id): JsonResponse
    {
        $airline = $request->attributes->get('authenticated_airline');

        return $this->success([
            'flight' => ['id' => $id, 'airline' => $airline->name],
        ], 'Flight details.');
    }

    public function issueTicket($request): JsonResponse
    {
        $request->attributes->get('authenticated_airline');

        return $this->success([
            'ticket' => ['reference' => strtoupper(bin2hex(random_bytes(4)))],
        ], 'Ticket issued.', 201);
    }

    public function getTicket($request, string $id): JsonResponse
    {
        return $this->success(['ticket' => ['id' => $id]], 'Ticket retrieved.');
    }

    public function showCurrentEnvironment($request): JsonResponse
    {
        $airline = $request->user();

        return $this->success([
            'active_environment' => $airline->active_environment,
            'is_production' => $airline->isInProduction(),
        ], 'Current environment.');
    }

    public function toggleEnvironment($request): JsonResponse
    {
        $airline = $request->user();
        $previous = $airline->active_environment;
        $newEnv = $request->environment;

        if ($previous === $newEnv) {
            return $this->error("You are already in {$newEnv} mode.", 422);
        }

        $airline->switchEnvironment($newEnv);

        $this->auditService->log($airline, AirlineAuditLog::EVENT_ENV_SWITCHED, [
            'from' => $previous,
            'to' => $newEnv,
        ]);

        return $this->success([
            'active_environment' => $newEnv,
            'is_production' => $airline->fresh()->isInProduction(),
        ], "Environment switched to {$newEnv}.");
    }

    public function getAudits($request): JsonResponse
    {
        $logs = $request->user()
            ->auditLogs()
            ->when($request->event, fn ($q) => $q->where('event', $request->event))
            ->latest()
            ->paginate($request->integer('per_page', 20));

        $data = AuditLogResource::collection($logs->items());

        return $this->withPagination($data, 'Audit logs retrieved.');
    }
}
