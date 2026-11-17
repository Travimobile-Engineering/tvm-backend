<?php

namespace App\Http\Middleware\Npis;

use App\Exceptions\NpisApiException;
use App\Models\Npis\AdminSession;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureOrganizerSession
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = (string) $request->bearerToken();

        if ($token === '') {
            throw NpisApiException::make(401, 'UNAUTHENTICATED', 'Sign in to the organizer dashboard.');
        }

        $session = AdminSession::query()
            ->with(['admin', 'grant'])
            ->where('token_hash', hash('sha256', $token))
            ->whereNull('revoked_at')
            ->first();

        if (! $session || $session->expires_at->isPast() || ! $session->admin || ! $session->admin->is_active) {
            throw NpisApiException::make(401, 'UNAUTHENTICATED', 'Sign in to the organizer dashboard.');
        }

        if (! $session->grant || ! $session->grant->isActive()) {
            throw NpisApiException::make(
                401,
                'UNAUTHENTICATED',
                'Organizer access has expired or the access code was changed. Enter the current access code.'
            );
        }

        $session->forceFill(['last_seen_at' => now()])->save();

        $request->attributes->set('npis_admin_session', $session);
        $request->attributes->set('npis_admin', $session->admin);
        $request->attributes->set('npis_permissions', $session->admin->permissions());

        Auth::setUser($session->admin);

        return $next($request);
    }
}
