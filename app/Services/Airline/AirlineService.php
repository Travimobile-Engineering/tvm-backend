<?php

namespace App\Services\Airline;

use App\Enum\PaymentType;
use App\Exports\AirlineManifestExport;
use App\Http\Resources\AirlineManifestResource;
use App\Http\Resources\ApiKeyResource;
use App\Http\Resources\AuditLogResource;
use App\Imports\AirlineManifestImport;
use App\Models\Airline;
use App\Models\AirlineAuditLog;
use App\Models\AirlineManifest;
use App\Models\User;
use App\Services\Client\HttpService;
use App\Services\Client\RequestOptions;
use App\Trait\HttpResponse;
use Illuminate\Http\JsonResponse;
use Maatwebsite\Excel\Excel;
use Maatwebsite\Excel\Validators\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AirlineService
{
    use HttpResponse;

    public function __construct(
        protected readonly ApiKeyService $keyService,
        protected readonly AuditService $auditService,
        private readonly ManifestService $manifestService,
        protected readonly HttpService $httpService,
    ) {}

    public function index($request): JsonResponse
    {
        $airlineId = $request->query('airline_id');

        if (blank($airlineId)) {
            return $this->error(null, 'Airline Id required', 422);
        }

        $airline = Airline::with('apiKeys')->find($airlineId);

        if (! $airline) {
            return $this->error(null, 'Airline not found', 404);
        }

        $keys = $airline->apiKeys()
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
        $airline = Airline::find($request->airline_id);

        if (! $airline) {
            return $this->error(null, 'Data not found', 404);
        }

        if ($airline->manifest_submission_method !== 'api') {
            return $this->error(null, 'Cannot generate API key. This airline is configured for upload-only mode.', 403);
        }

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
        $airlineId = $request->query('airline_id');

        if (blank($airlineId)) {
            return $this->error(null, 'Airline Id required', 422);
        }

        $airline = Airline::with('apiKeys')->find($airlineId);

        if (! $airline) {
            return $this->error(null, 'Airline not found!', 404);
        }

        if ($airline->manifest_submission_method !== 'api') {
            return $this->error(null, 'Cannot view API key. This airline is configured for upload-only mode.', 403);
        }

        $key = $airline->apiKeys()->findOrFail($id);

        return $this->success([
            'key' => new ApiKeyResource($key),
        ], 'API key details.');
    }

    public function rotate($request, int $id): JsonResponse
    {
        $airline = Airline::with('apiKeys')->find($request->airline_id);

        if (! $airline) {
            return $this->error(null, 'Airline not found!', 404);
        }

        if ($airline->manifest_submission_method !== 'api') {
            return $this->error(null, 'Cannot rotate API key. This airline is configured for upload-only mode.', 403);
        }

        $oldKey = $airline->apiKeys()->active()->findOrFail($id);

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
        $airlineId = $request->query('airline_id');

        if (blank($airlineId)) {
            return $this->error(null, 'Airline Id required', 422);
        }

        $airline = Airline::with('apiKeys')->find($airlineId);

        if (! $airline) {
            return $this->error(null, 'Airline not found!', 404);
        }

        if ($airline->manifest_submission_method !== 'api') {
            return $this->error(null, 'Cannot revoke API key. This airline is configured for upload-only mode.', 403);
        }

        $key = $airline->apiKeys()->active()->findOrFail($id);

        $this->keyService->revoke($key);

        return $this->success(null, 'API key revoked successfully.');
    }

    public function showCurrentEnvironment($request): JsonResponse
    {
        $airlineId = $request->query('airline_id');

        if (blank($airlineId)) {
            return $this->error(null, 'Airline Id required', 422);
        }

        $airline = Airline::find($airlineId);

        if (! $airline) {
            return $this->error(null, 'Airline not found', 404);
        }

        return $this->success([
            'active_environment' => $airline->active_environment,
            'is_production' => $airline->isInProduction(),
        ], 'Current environment.');
    }

    public function toggleEnvironment($request): JsonResponse
    {
        $airline = Airline::find($request->airline_id);

        if (! $airline) {
            return $this->error(null, 'Airline not found', 404);
        }

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

    public function overview($userId)
    {
        $user = User::with(['airline'])->find($userId);

        if (! $user) {
            return $this->error(null, 'User not found', 404);
        }

        $airline = Airline::with(['manifests', 'wallet'])
            ->withCount('manifests')
            ->find($user->airline->id);

        if (! $airline) {
            return $this->error(null, 'User does not belong to an airline.', 403);
        }

        $data = [
            'total_submission' => $airline->manifests_count,
            'api_health' => [],
            'wallet_balance' => $airline?->wallet->balance,
            'recent_submissions' => $airline->manifests()->take(10)->latest()->get(),
        ];

        return $this->success($data, 'Overview data');
    }

    public function getManifests($request)
    {
        $query = AirlineManifest::with('airline')
            ->when($request->airline_id, fn ($q) => $q->where('airline_id', $request->airline_id))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->customer, fn ($q) => $q->where('customer', 'like', "%{$request->customer}%"))
            ->when($request->aircraft_registration, fn ($q) => $q->where('aircraft_registration', $request->aircraft_registration))
            ->when($request->flight_date, fn ($q) => $q->whereDate('flight_date', $request->flight_date))
            ->when($request->flight_date_from, fn ($q) => $q->whereDate('flight_date', '>=', $request->flight_date_from))
            ->when($request->flight_date_to, fn ($q) => $q->whereDate('flight_date', '<=', $request->flight_date_to))
            ->when($request->search, fn ($q) => $q->where(function ($q) use ($request) {
                $q->whereAny(['manifest_number', 'customer'], 'like', "%{$request->search}%");
            }))
            ->latest('flight_date');

        $perPage = min((int) ($request->per_page ?? 15), 100);
        $manifests = $query->paginate($perPage);

        return $this->withPagination(AirlineManifestResource::collection($manifests), 'Manifests');
    }

    public function createManifest($request)
    {
        $airline = Airline::with('wallet')->find($request->airline_id);

        if (! $airline) {
            return $this->error(null, 'Airline not found', 404);
        }

        try {
            $manifest = $this->manifestService->create($airline, $request->validated());

            return $this->success(new AirlineManifestResource($manifest), 'Manifest created successfully.', 201);

        } catch (\Throwable $e) {
            return $this->error(null, "Failed to create manifest: {$e}", 400);
        }
    }

    public function getManifest($manifest)
    {
        $manifest->load(['airline', 'crews', 'passengers', 'cargos']);

        return $this->success(new AirlineManifestResource($manifest), 'Manifest detail');
    }

    public function uploadManifest($request)
    {
        try {
            $import = new AirlineManifestImport((int) $request->validated('airline_id'));

            Excel::import($import, $request->file('file'));

            if (! empty($import->errors)) {
                return $this->error(['errors' => $import->errors], 'File processed but contained errors.', 422);
            }

            if ($import->manifest === null) {
                return $this->error(null, 'No valid manifest data found in the uploaded file.', 422);
            }

            $data = new AirlineManifestResource($import->manifest->load(['airline', 'crews', 'passengers', 'cargos']));

            return $this->success($data, 'Manifest uploaded and created successfully.', 201);
        } catch (ValidationException $e) {
            $failures = collect($e->failures())->map(fn ($f) => [
                'row' => $f->row(),
                'field' => $f->attribute(),
                'errors' => $f->errors(),
            ]);

            return $this->error(['failures' => $failures], 'Validation failed in uploaded file.', 422);
        } catch (\Throwable $e) {
            return $this->error(null, "Failed to process uploaded file: {$e->getMessage()}", 400);
        }
    }

    public function exportManifest($manifest, $request): BinaryFileResponse
    {
        $manifest->load(['airline', 'crews', 'passengers', 'cargos']);

        $format = strtolower($request->query('format', 'xlsx'));
        $filename = "{$manifest->manifest_number}.{$format}";

        $writerType = match ($format) {
            'csv' => Excel::CSV,
            default => Excel::XLSX,
        };

        return Excel::download(new AirlineManifestExport($manifest), $filename, $writerType);
    }

    public function updateManifest($request, $manifest)
    {
        if ($manifest->status === 'closed') {
            return $this->error(null, 'A closed manifest cannot be edited.', 422);
        }

        try {
            $updated = $this->manifestService->update($manifest, $request->validated());

            return $this->success(new AirlineManifestResource($updated), 'Manifest updated successfully.');
        } catch (\Throwable $e) {
            return $this->error(null, "Failed to update manifest: {$e->getMessage()}", 400);
        }
    }

    public function destroyManifest($manifest): JsonResponse
    {
        if ($manifest->status === 'closed') {
            return $this->error(null, 'A closed manifest cannot be edited.', 422);
        }

        $manifest->delete();

        return $this->success(null, 'Manifest deleted successfully.');
    }

    public function topUp($request)
    {
        $airline = Airline::findOrFail($request->input('airline_id'));

        $amount = $request->input('amount') * 100;

        $callbackUrl = $request->input('redirect_url');
        if (! filter_var($callbackUrl, FILTER_VALIDATE_URL)) {
            return response()->json(['error' => 'Invalid callback URL'], 400);
        }

        $paymentDetails = [
            'email' => $request->input('email') ?? $airline->email,
            'amount' => $amount,
            'currency' => 'NGN',
            'metadata' => json_encode([
                'airline_id' => $request->input('airline_id'),
                'payment_type' => PaymentType::FUND_WALLET,
                'service' => 'transport',
                'is_airline' => true,
            ]),
            'payment_method' => 'paystack',
            'callback_url' => (string) trim($request->input('redirect_url')),
        ];

        try {
            $url = config('services.payment.url').'/paystack/initialize';
            $response = $this->httpService->post(
                $url,
                new RequestOptions(
                    data: $paymentDetails
                )
            );

            if ($response->failed()) {
                return [
                    'status' => false,
                    'message' => $response['message'] ?? 'Failed to initialize payment',
                    'data' => null,
                ];
            }

            $data = $response->json();

            return [
                'status' => 'success',
                'message' => $data['message'],
                'data' => $data['data'],
            ];
        } catch (\Exception $e) {
            return $this->error(null, $e->getMessage(), 500);
        }
    }
}
