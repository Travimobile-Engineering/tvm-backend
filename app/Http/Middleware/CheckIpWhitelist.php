<?php

namespace App\Http\Middleware;

use App\Models\IpWhitelist;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class CheckIpWhitelist
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $ip = $request->ip();

        $allowed = Cache::remember("ip_whitelist:{$ip}", 60, function () use ($ip) {

            // Check if whitelist table has any records
            $hasWhitelist = IpWhitelist::allowed()->exists();

            // If no records → allow all
            if (! $hasWhitelist) {
                return true;
            }

            // If records exist → enforce whitelist
            return IpWhitelist::allowed()
                ->where('ip_address', $ip)
                ->exists();
        });

        if (! $allowed) {
            return response()->json([
                'success' => false,
                'message' => 'Your IP address is not authorised to access this resource.',
                'ip' => $ip,
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
