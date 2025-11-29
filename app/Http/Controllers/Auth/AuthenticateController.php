<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Services\Auth\LoginService;

class AuthenticateController extends Controller
{
    public function __construct(
        protected LoginService $service,
    ) {}

    // login method to authenticate user and issue JWT
    public function login(LoginRequest $request)
    {
        $result = $this->service->login($request);

        return response()->json($result, $result['code'] ?? 200);
    }

    public function securityAgentLogin(LoginRequest $request)
    {
        $result = $this->service->securityAgentLogin($request);

        return response()->json($result, $result['code'] ?? 200);
    }

    // logout method to invalidate the token
    public function logout()
    {
        $this->service->logout();
    }

    // New auth login for users
    public function authLogin(LoginRequest $request)
    {
        return $this->service->authLogin($request);
    }

    // New auth login for security agencies
    public function agencyLogin(LoginRequest $request)
    {
        return $this->service->agencyLogin($request);
    }
}
