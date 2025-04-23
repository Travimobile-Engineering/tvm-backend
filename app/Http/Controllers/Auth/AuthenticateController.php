<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use App\Services\Auth\AuthService;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Http\Requests\LoginRequest;
use App\Services\Auth\LoginService;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Tymon\JWTAuth\Exceptions\JWTException;

class AuthenticateController extends Controller
{

    public function __construct(
        protected LoginService $service,
    ){}
    
    //login method to authenticate user and issue JWT
    public function login(LoginRequest $request){
        $result = $this->service->login($request);
        return response()->json($result, $result['code'] ?? 200);
    }

    public function securityAgentLogin(LoginRequest $request){
        $result = $this->service->securityAgentLogin($request);
        return response()->json($result, $result['code'] ?? 200);
    }

    //logout method to invalidate the token
    public function logout(){
        $this->service->logout();
    }
}
