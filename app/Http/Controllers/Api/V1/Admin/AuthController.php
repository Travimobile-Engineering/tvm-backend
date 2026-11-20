<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\NpisApiController;
use App\Http\Requests\Npis\AccessCodeRequest;
use App\Http\Requests\Npis\AdminAccessRequest;
use App\Http\Requests\Npis\AdminSessionRequest;
use App\Models\Npis\AdminSession;
use App\Services\Npis\OrganizerAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends NpisApiController
{
    public function __construct(private readonly OrganizerAuthService $auth) {}

    public function access(AdminAccessRequest $request): JsonResponse
    {
        return $this->success(
            $this->auth->grantAccess(
                (string) $request->validated('code'),
                $request->ip(),
                $request->userAgent(),
            ),
            'Organizer access granted',
            201
        );
    }

    public function session(AdminSessionRequest $request): JsonResponse
    {
        $grant = $request->attributes->get('npis_access_grant');

        return $this->success(
            $this->auth->createSession(
                (string) $request->validated('idToken'),
                $grant,
                $request->ip(),
                $request->userAgent(),
            ),
            'Organizer session created successfully',
            201
        );
    }

    public function me(Request $request): JsonResponse
    {
        $admin = $this->admin($request);
        $session = $request->attributes->get('npis_admin_session');

        return $this->success([
            'userId' => 'usr_'.$admin->id,
            'name' => $admin->name,
            'email' => $admin->email,
            'role' => $admin->role->value,
            'roleLabel' => $admin->roleLabel(),
            'permissions' => $admin->permissions(),
            'lastLoginAt' => $admin->last_login_at?->toIso8601String(),
            'sessionExpiresAt' => $session instanceof AdminSession
                ? $session->expires_at->toIso8601String()
                : null,
        ], 'Organizer session retrieved successfully');
    }

    public function rotateAccessCode(AccessCodeRequest $request): JsonResponse
    {
        $this->auth->rotateAccessCode((string) $request->validated('code'), $this->admin($request));

        return $this->success(null, 'Organizer access code changed successfully', 204);
    }
}
