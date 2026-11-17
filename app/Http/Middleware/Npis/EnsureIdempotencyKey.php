<?php

namespace App\Http\Middleware\Npis;

use App\Exceptions\NpisApiException;
use App\Models\Npis\IdempotencyRecord;
use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureIdempotencyKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $key = (string) $request->header('Idempotency-Key', '');

        if ($key === '') {
            throw NpisApiException::make(
                400,
                'VALIDATION_FAILED',
                'The Idempotency-Key header is required for this request.',
                ['fields' => ['Idempotency-Key' => 'Required']]
            );
        }

        if (strlen($key) > 190) {
            throw NpisApiException::make(422, 'VALIDATION_FAILED', 'The Idempotency-Key is too long.', [
                'fields' => ['Idempotency-Key' => 'Must be 190 characters or fewer'],
            ]);
        }

        $scope = $this->scope($request);
        $requestHash = hash('sha256', $request->method().'|'.$request->path().'|'.$this->canonicalJson($request->all()));

        $existing = IdempotencyRecord::query()
            ->where('scope', $scope)
            ->where('idempotency_key', $key)
            ->first();

        if ($existing) {
            if ($existing->expires_at->isPast()) {
                $existing->delete();
            } else {
                if (! hash_equals($existing->request_hash, $requestHash)) {
                    throw NpisApiException::make(
                        422,
                        'IDEMPOTENCY_KEY_REUSED',
                        'This Idempotency-Key was already used with a different request body.'
                    );
                }

                return response()
                    ->json($existing->response_body, $existing->status_code)
                    ->header('Idempotent-Replayed', 'true');
            }
        }

        $response = $next($request);

        if ($response->getStatusCode() < 500) {
            $body = $response instanceof JsonResponse
                ? $response->getData(true)
                : json_decode($response->getContent(), true);

            try {
                IdempotencyRecord::create([
                    'scope' => $scope,
                    'idempotency_key' => $key,
                    'method' => $request->method(),
                    'path' => $request->path(),
                    'request_hash' => $requestHash,
                    'status_code' => $response->getStatusCode(),
                    'response_body' => $body,
                    'expires_at' => now()->addSeconds((int) config('npis.idempotency.ttl', 86400)),
                ]);
            } catch (QueryException) {
                // A concurrent request stored the key first. Replay it when
                // the request body matches, otherwise surface the conflict.
                $stored = IdempotencyRecord::query()
                    ->where('scope', $scope)
                    ->where('idempotency_key', $key)
                    ->first();

                if ($stored && ! hash_equals($stored->request_hash, $requestHash)) {
                    throw NpisApiException::make(
                        422,
                        'IDEMPOTENCY_KEY_REUSED',
                        'This Idempotency-Key was already used with a different request body.'
                    );
                }
            }
        }

        return $response;
    }

    private function scope(Request $request): string
    {
        $admin = $request->attributes->get('npis_admin');
        if ($admin) {
            return 'admin:'.$admin->id;
        }

        $contact = $request->attributes->get('npis_contact');
        if ($contact) {
            return 'participant:'.$contact->id;
        }

        return 'anonymous:'.hash('sha256', (string) $request->ip());
    }

    private function canonicalJson(mixed $value): string
    {
        if (is_array($value)) {
            if (array_is_list($value)) {
                return '['.implode(',', array_map(fn (mixed $item): string => $this->canonicalJson($item), $value)).']';
            }

            ksort($value);

            return '{'.implode(',', array_map(
                fn (string $key): string => json_encode($key).':'.$this->canonicalJson($value[$key]),
                array_keys($value)
            )).'}';
        }

        return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
