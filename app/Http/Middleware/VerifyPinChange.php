<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class VerifyPinChange
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::guard('api')->user();

        if (! Cache::has('pin_change_verified_'.$user->id)) {
            return response()->json(['message' => 'OTP verification required before changing PIN'], 403);
        }

        return $next($request);
    }
}
