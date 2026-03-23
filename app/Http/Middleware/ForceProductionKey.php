<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForceProductionKey
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $environment = $request->attributes->get('api_environment');

        if ($environment !== 'production') {
            return response()->json([
                'success' => false,
                'message' => 'This endpoint requires production credentials (pk_live_ / sk_live_).',
                'code' => 'PRODUCTION_KEY_REQUIRED',
            ], 403);
        }

        return $next($request);
    }
}
