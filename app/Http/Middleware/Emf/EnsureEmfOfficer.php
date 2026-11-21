<?php

namespace App\Http\Middleware\Emf;

use App\Exceptions\NpisApiException;
use App\Models\Emf\OfficerSession;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmfOfficer
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = (string) $request->bearerToken();

        if ($token === '') {
            throw NpisApiException::make(401, 'UNAUTHENTICATED', 'Sign in to the Manifest Checker.');
        }

        $session = OfficerSession::query()
            ->with('officer')
            ->where('token_hash', hash('sha256', $token))
            ->whereNull('revoked_at')
            ->first();

        if (! $session || $session->expires_at->isPast() || ! $session->officer) {
            throw NpisApiException::make(401, 'UNAUTHENTICATED', 'Your session has expired. Sign in again.');
        }

        if ($session->officer->status === 'suspended') {
            throw NpisApiException::make(403, 'FORBIDDEN', 'This account is suspended.');
        }

        $request->attributes->set('emf_officer', $session->officer);
        $request->attributes->set('emf_session', $session);
        $request->attributes->set('emf_permissions', $session->officer->permissions());

        return $next($request);
    }
}
