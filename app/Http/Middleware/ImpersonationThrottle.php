<?php

namespace App\Http\Middleware;

use App\Trait\HttpResponse;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class ImpersonationThrottle
{
    use HttpResponse;

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $driverId = $request->user_id;

        if (! $driverId) {
            return $this->error(null, 'User id is required', 400);
        }

        $cacheKey = "impersonation_attempts:driver_{$driverId}";
        $blockKey = "blocked_driver_{$driverId}";

        if (Cache::has($blockKey)) {
            return $this->error(
                null,
                'You have been blocked from impersonating this driver',
                Response::HTTP_TOO_MANY_REQUESTS
            );
        }

        if (RateLimiter::tooManyAttempts($cacheKey, 3)) {
            Cache::put($blockKey, true, now()->addDay());

            return $this->error(
                null,
                'Too many failed attempts. You are blocked for 24 hours.',
                Response::HTTP_TOO_MANY_REQUESTS
            );
        }

        return $next($request);
    }
}
