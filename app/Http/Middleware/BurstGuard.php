<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class BurstGuard
{
    public function __construct(
        private RateLimiter $limiter,
        private int $maxPer5s = 5,
        private int $maxPer60s = 20,
        private int $banSeconds = 300
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $route = $request->route()?->getName() ?? $request->path();
        $ip = $request->ip();
        $uid = (string) ($request->user()?->getAuthIdentifier() ?? 'guest');

        // Check existing bans (Cache-based)
        foreach ([$this->banKey($route, "ip:$ip"), $this->banKey($route, "uid:$uid")] as $banKey) {
            if (Cache::has($banKey)) {
                return $this->tooMany($this->banTtl($banKey));
            }
        }

        // Rate-limit across two short windows using RateLimiter
        foreach (["ip:$ip", "uid:$uid"] as $dim) {
            if (! $this->withinBudget($route, $dim)) {
                $banKey = $this->banKey($route, $dim);
                Cache::put($banKey, now()->addSeconds($this->banSeconds)->timestamp, $this->banSeconds);

                return $this->tooMany($this->banSeconds);
            }
        }

        return $next($request);
    }

    private function withinBudget(string $route, string $dim): bool
    {
        // per-5s
        $k5 = "rate:{$route}:{$dim}:5";
        if ($this->limiter->tooManyAttempts($k5, $this->maxPer5s, 5)) {
            return false;
        }
        $this->limiter->hit($k5, 5);

        // per-60s
        $k60 = "rate:{$route}:{$dim}:60";
        if ($this->limiter->tooManyAttempts($k60, $this->maxPer60s, 60)) {
            return false;
        }
        $this->limiter->hit($k60, 60);

        return true;
    }

    private function banKey(string $route, string $dim): string
    {
        return "ban:{$route}:{$dim}";
    }

    private function banTtl(string $banKey): int
    {
        $ts = Cache::get($banKey);

        return $ts ? max(0, $ts - time()) : $this->banSeconds;
    }

    private function tooMany(int $retryAfter): Response
    {
        return response()->json([
            'message' => 'Too many requests detected. Please slow down.',
        ], 429)->withHeaders([
            'Retry-After' => $retryAfter,
            'X-RateLimit-Reason' => 'burst-guard',
        ]);
    }
}
