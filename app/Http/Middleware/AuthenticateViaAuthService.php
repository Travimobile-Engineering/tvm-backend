<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Trait\HttpResponse;
use Illuminate\Support\Facades\Http;

class AuthenticateViaAuthService
{
    use HttpResponse;
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();
        $service = config('services.auth_service.name');

        if (! $request->hasHeader('X-App-Service')) {
            return $this->error(null, 'Service name not configured', 500);
        }

        if (!$token) {
            return $this->error(null, 'Token missing', 401);
        }

        $response = Http::withToken($token)
            ->withHeaders([
                'X-App-Service' => $service,
                config('security.auth_header_key') => config('security.auth_header_value'),
            ])
            ->get(config('services.auth_service.url') . '/validate');

        $data = $response->json();

        if (!$data['status']) {
            return $this->error(null, "Unauthenticated! {$data['message']}", 401);
        }

        if ($data['data']['valid']) {
            $request->merge(['auth_user' => $data['data']['user']]);
            return $next($request);
        }

        return $this->error(null, "Unauthorized! {$data['message']}", 401);
    }
}
