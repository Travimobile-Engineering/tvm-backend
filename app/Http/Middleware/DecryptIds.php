<?php

namespace App\Http\Middleware;

use App\Trait\HttpResponse;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Symfony\Component\HttpFoundation\Response;

class DecryptIds
{
    use HttpResponse;

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        // Body (json/form) — STRICT for certain keys
        $request->merge($this->strictDecryptIds($request->all()));

        // Query params – NON-STRICT (keep existing behavior)
        if ($query = $request->query()) {
            $request->query->replace($this->decryptIds($query));
        }

        // Route parameters – STRICT
        if ($route = $request->route()) {
            foreach ($route->parameters() as $key => $value) {
                if (is_string($value) && $this->shouldDecryptKey($key)) {
                    $decrypted = $this->strictDecrypt($value);
                    $route->setParameter($key, $decrypted);
                }
            }
        }

        return $next($request);
    }

    /**
     * Recursively enforce strict decrypt for keys that must be encrypted.
     */
    protected function strictDecryptIds(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->strictDecryptIds($value);
                continue;
            }

            if ($this->shouldDecryptKey($key)) {
                if (!is_string($value) || $value === '') {
                    abort(422, "Field '$key' must be a string.");
                }
                $data[$key] = $this->strictDecrypt($value); // aborts on invalid ciphertext
            }
        }

        return $data;
    }

    /**
     * STRICT decrypt for route params: abort if invalid/not encrypted.
     */
    protected function strictDecrypt(string $value): string
    {
        try {
            return Crypt::decryptString($value);
        } catch (\Throwable $e) {
            try {
                $v = strtr($value, '-_', '+/');
                $pad = strlen($v) % 4;
                if ($pad) {
                    $v .= str_repeat('=', 4 - $pad);
                }
                return Crypt::decryptString($v);
            } catch (\Throwable $e2) {
                abort(404, 'Not found');
            }
        }
    }

    protected function decryptIds(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->decryptIds($value);
                continue;
            }

            if ($this->shouldDecryptKey($key) && is_string($value)) {
                $data[$key] = $this->tryDecrypt($value);
            }
        }

        return $data;
    }

    protected function shouldDecryptKey(string $key): bool
    {
        // Exact matches you care about
        static $exact = ['id', 'user', 'user_id', 'driver_id', 'vehicle_id', 'booking_id', 'trip_id', 'notification_id', 'agent_id', 'classification_id'];
        if (in_array($key, $exact, true)) {
            return true;
        }

        // Any *_id will be attempted
        if (str_ends_with($key, '_id')) {
            return true;
        }

        return false;
    }

    protected function tryDecrypt(string $value): mixed
    {
        try {
            return Crypt::decryptString($value);
        } catch (\Throwable $e) {
            return $value; // not encrypted? keep as-is
        }
    }
}
