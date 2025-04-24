<?php

namespace App\Http\Middleware;

use App\Enum\UserType;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AgentAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::guard('api')->user();

        if (!$user || !in_array($user->user_category, [UserType::AGENT->value, UserType::DRIVER->value])) {
            return response()->json(['message' => "Unauthorized: You aren't authorized to perform this action."], 401);
        }

        return $next($request);
    }
}
