<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureHttps
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isSecure() && app()->environment('production')) {
            return response()->json([
                'success' => false,
                'message' => 'HTTPS is required. Please resend your request over a secure connection.',
                'code' => 'HTTPS_REQUIRED',
            ], 403);
        }

        return $next($request);
    }
}
