<?php

namespace App\Http\Middleware\Npis;

use App\Exceptions\NpisApiException;
use App\Models\Npis\OrganizerAccessGrant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccessGrant
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = (string) $request->bearerToken();

        if ($token === '') {
            throw NpisApiException::make(401, 'UNAUTHENTICATED', 'Enter the organizer access code first.');
        }

        $grant = OrganizerAccessGrant::query()
            ->where('token_hash', hash('sha256', $token))
            ->whereNull('revoked_at')
            ->first();

        if (! $grant || $grant->expires_at->isPast()) {
            throw NpisApiException::make(401, 'UNAUTHENTICATED', 'Enter the organizer access code first.');
        }

        $request->attributes->set('npis_access_grant', $grant);

        return $next($request);
    }
}
