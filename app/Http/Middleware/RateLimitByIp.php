<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class RateLimitByIp
{
    /**
     * @param  int  $maxAttempts  Max requests allowed in the window
     * @param  int  $decaySeconds  Window length in seconds
     */
    public function handle(Request $request, Closure $next, int $maxAttempts = 30, int $decaySeconds = 10): Response
    {
        $ip = $request->ip();
        $key = "rate_limit:{$ip}:".floor(time() / $decaySeconds);

        $hits = (int) Cache::get($key, 0);

        if ($hits >= $maxAttempts) {
            return response()->json([
                'success' => false,
                'message' => 'Too many requests. Slow down.',
                'retry_after' => $decaySeconds - (time() % $decaySeconds),
            ], Response::HTTP_TOO_MANY_REQUESTS);
        }

        // Increment and set TTL only on the first hit
        if ($hits === 0) {
            Cache::put($key, 1, $decaySeconds);
        } else {
            Cache::increment($key);
        }

        return $next($request)
            ->header('X-RateLimit-Limit', $maxAttempts)
            ->header('X-RateLimit-Remaining', max(0, $maxAttempts - $hits - 1));
    }
}
