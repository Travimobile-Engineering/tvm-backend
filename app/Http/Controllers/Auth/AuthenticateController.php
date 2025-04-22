<?php

namespace App\Http\Controllers\Auth;

use App\Services\Auth\LoginService;
use Illuminate\Http\Request;
use App\Http\Requests\LoginRequest;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;

class AuthenticateController extends Controller
{
    public function __construct(
        protected LoginService $service,
    ){}

    //login method to authenticate user and issue JWT
    public function login(LoginRequest $request)
    {
        $result = $this->service->login($request);
        return response()->json($result, $result['code'] ?? 200);
    }

    public function securityAgentLogin(LoginRequest $request){
        $result = $this->service->securityAgentLogin($request);
        return response()->json($result, $result['code'] ?? 200);
    }

    //logout method to invalidate the token
    public function logout()
    {
        $this->service->logout();
    }

    public function authLogin(LoginRequest $request)
    {
        return $this->service->authLogin($request);
    }

    public function agencyLogin(LoginRequest $request)
    {
        return $this->service->agencyLogin($request);
    }
}
