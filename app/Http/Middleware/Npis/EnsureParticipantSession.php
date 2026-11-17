<?php

namespace App\Http\Middleware\Npis;

use App\Exceptions\NpisApiException;
use App\Models\Npis\ParticipantSession;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureParticipantSession
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = (string) $request->bearerToken();

        if ($token === '') {
            throw NpisApiException::make(401, 'UNAUTHENTICATED', 'Verify your phone number or email to continue.');
        }

        $session = ParticipantSession::query()
            ->with('contact')
            ->where('token_hash', hash('sha256', $token))
            ->whereNull('revoked_at')
            ->first();

        if (! $session || $session->expires_at->isPast() || ! $session->contact) {
            throw NpisApiException::make(401, 'UNAUTHENTICATED', 'Your session has expired. Verify your phone number or email again to continue.');
        }

        $request->attributes->set('npis_participant_session', $session);
        $request->attributes->set('npis_contact', $session->contact);
        $request->attributes->set('npis_permissions', ['*']);

        return $next($request);
    }
}
