<?php

namespace App\Http\Middleware\Npis;

use App\Exceptions\NpisApiException;
use BackedEnum;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $admin = $request->attributes->get('npis_admin');

        if (! $admin) {
            throw NpisApiException::make(401, 'UNAUTHENTICATED', 'Sign in to the organizer dashboard.');
        }

        if (! $admin->hasPermission($permission)) {
            throw NpisApiException::make(403, 'FORBIDDEN', 'Your role does not have access to this action.', [
                'requiredPermission' => $permission,
                'role' => $admin->role instanceof BackedEnum ? $admin->role->value : (string) $admin->role,
            ]);
        }

        return $next($request);
    }
}
