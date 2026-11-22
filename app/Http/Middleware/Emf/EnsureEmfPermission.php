<?php

namespace App\Http\Middleware\Emf;

use App\Exceptions\NpisApiException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmfPermission
{
    public function handle(Request $request, Closure $next, string ...$permissionParts): Response
    {
        $permission = implode(':', $permissionParts);
        $officer = $request->attributes->get('emf_officer');

        if (! $officer) {
            throw NpisApiException::make(401, 'UNAUTHENTICATED', 'Sign in to the Manifest Checker.');
        }

        if (! $officer->hasPermission($permission)) {
            throw NpisApiException::make(403, 'FORBIDDEN', 'You do not have access to this action.', [
                'requiredPermission' => $permission,
                'role' => $officer->role,
            ]);
        }

        return $next($request);
    }
}
