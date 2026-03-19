<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AllowCORS
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
        // ->headers('Access-Control-Allow-Origin', '*')
        // ->headers('Access-Control-Allow-Methods', '*')
        // ->headers('Access-Control-Allow-Credentials', true)
        // ->headers('Access-Control-Allow-Headers', 'X-Requested-With,Content-Type,X-Token-Auth,Origin,Authorization')
        // ->headers('Accept', 'application/json');
    }
}
