<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ValidateHeader
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $headerName = config('security.header_key', 'X-SECURE-AUTH');
        $expectedValue = config('security.header_value');

        $receivedValue = $request->header($headerName);

        if (!$receivedValue) {
            Log::warning("Missing required header: {$headerName}");
            return response()->json(['error' => 'Unauthorized access. Missing required header.'], 401);
        }

        if ($receivedValue !== $expectedValue) {
            Log::warning("Invalid header value for {$headerName}: {$receivedValue}");
            return response()->json(['error' => 'Unauthorized access. Invalid header value.'], 401);
        }

        return $next($request);
    }
}
