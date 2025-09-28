<?php

namespace App\Http\Middleware;

use App\Trait\HttpResponse;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class TransactionReplayShield
{
    use HttpResponse;

    public function __construct(
        private int $windowSeconds = 15,
        private array $payloadBlacklist = ['_token', 'csrf_token', '_method', 'timestamp', 'ts', 'nonce', 'trace_id', 'request_id', 'reference']
    ) {}

    /**
     * Use as: ->middleware('tx.shield:recipient_id,amount,currency')
     * If no keys are passed, it will hash the whole body minus $payloadBlacklist.
     */
    public function handle(Request $request, Closure $next, ...$keys): Response
    {
        if (! in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return $next($request);
        }

        // ----- Stable fingerprint -----
        $actorId = $request->user()?->getAuthIdentifier();
        $actor = (string) ($actorId ?? 'guest');
        $routeName = $request->route()?->getName() ?? '';
        $path = $request->path();
        $routeParams = $this->scalarize($request->route()?->parameters() ?? []);

        // Normalize keys from middleware params (comma-separated support already handled by Laravel)
        $keys = array_values(array_filter(array_map('trim', $keys)));

        // Choose payload fields:
        // - If keys were provided via middleware params, pick those (supports dot-notation).
        // - Else, auto mode: use entire body minus volatile fields.
        $body = $request->all();
        $body = ! empty($keys)
            ? $this->pickKeys($body, $keys) // per-route whitelist
            : Arr::except($body, $this->payloadBlacklist); // auto mode

        $payload = [
            'query' => Arr::sortRecursive($request->query()),
            'body' => Arr::sortRecursive($body),
        ];

        $routeSig = [
            'name' => $routeName,
            'path' => $path, // concrete path
            'params' => Arr::sortRecursive($routeParams),
            'method' => $request->method(),
        ];

        $sig = hash('sha256', json_encode([$actor, $routeSig, $payload], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $now = now();
        $exp = now()->addSeconds($this->windowSeconds);

        // ----- Fast path: try unique insert -----
        if ($this->tryInsertUnique($sig, $actorId, $routeName ?: $path, $exp, $now)) {
            return $next($request);
        }

        // ----- On conflict: check expiry and reclaim if expired -----
        $row = DB::table('tx_replay_guards')->where('key', $sig)->first();

        if ($row) {
            $rowExp = \Carbon\Carbon::parse($row->expires_at);

            if ($rowExp->lte($now)) {
                // Expired → atomically reclaim so new request can proceed
                $affected = DB::table('tx_replay_guards')
                    ->where('key', $sig)
                    ->where('expires_at', '<=', $now)
                    ->update(['expires_at' => $exp, 'updated_at' => $now]);

                if ($affected === 1) {
                    return $next($request);
                }

                // Lost race → refresh row and fall through
                // $row = DB::table('tx_replay_guards')->where('key', $sig)->first();
                // $rowExp = $row ? \Carbon\Carbon::parse($row->expires_at) : $now->addSeconds($this->windowSeconds);
            }

            // Still within window → early return
            // $remaining = max(1, $now->diffInSeconds($rowExp, false));

            return $this->error(null, 'Duplicate request detected. Please wait a moment and try again.', 409);
        }

        // Rare: row missing after conflict → proceed
        return $next($request);
    }

    private function tryInsertUnique(string $sig, $actorId, string $routeSig, \Illuminate\Support\Carbon $expiresAt, \Illuminate\Support\Carbon $now): bool
    {
        try {
            DB::table('tx_replay_guards')->insert([
                'key' => $sig,
                'actor_id' => $actorId,
                'route_sig' => $routeSig,
                'expires_at' => $expiresAt,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            return true;
        } catch (QueryException $e) {
            if ($this->isUniqueViolation($e)) {
                return false;
            }
            throw $e;
        }
    }

    private function isUniqueViolation(QueryException $e): bool
    {
        $sqlState = $e->errorInfo[0] ?? null; // '23000' (MySQL/SQLite)
        $driverCode = (int) ($e->errorInfo[1] ?? 0); // 1062 (MySQL)

        return $sqlState === '23505' || ($sqlState === '23000' && in_array($driverCode, [1062, 19], true));
    }

    private function scalarize(array $params): array
    {
        return collect($params)->map(function ($v) {
            if ($v instanceof Model) {
                return $v->getKey();
            }
            if (is_object($v) && method_exists($v, '__toString')) {
                return (string) $v;
            }
            if (is_array($v)) {
                return $this->scalarize($v);
            }

            return $v;
        })->all();
    }

    /**
     * Pick nested keys (dot-notation supported). Keeps array shape of selected keys only.
     */
    private function pickKeys(array $data, array $keys): array
    {
        $out = [];
        foreach ($keys as $k) {
            $val = data_get($data, $k, null);
            if ($val !== null) {
                data_set($out, $k, $val);
            }
        }

        return $out;
    }
}
