<?php

namespace App\Http\Controllers\Api\V1\ManifestChecker;

use App\Http\Requests\Emf\ActivateOfficerRequest;
use App\Http\Requests\Emf\LoginRequest;
use App\Services\Emf\EmfAuthService;
use App\Services\Emf\EmfOfficerProvisioningService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends EmfApiController
{
    public function __construct(
        private readonly EmfAuthService $auth,
        private readonly EmfOfficerProvisioningService $provisioning,
    ) {}

    public function login(LoginRequest $request): JsonResponse
    {
        return $this->success($this->auth->login(
            (string) $request->string('serviceNumber'),
            (string) $request->string('password'),
            $request,
        ), 'Signed in to the Manifest Checker.');
    }

    public function activate(ActivateOfficerRequest $request): JsonResponse
    {
        return $this->success($this->provisioning->activate(
            (string) $request->string('serviceNumber'),
            (string) $request->string('token'),
            (string) $request->string('password'),
            $request,
        ), 'Officer account activated.');
    }

    public function me(Request $request): JsonResponse
    {
        return $this->success($this->auth->officerContract($this->officer($request)), 'Officer profile.');
    }

    public function logout(Request $request): JsonResponse
    {
        $this->auth->logout($this->officer($request), $request);

        return $this->success(null, 'Signed out.');
    }
}
