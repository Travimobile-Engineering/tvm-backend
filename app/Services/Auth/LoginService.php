<?php

namespace App\Services\Auth;

use App\Enum\UserType;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Exceptions\JWTException;

class LoginService
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    public function login($request) :array{
        $emailOrPhone = filter_var($request->email, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone_number';
        $credentials = $request->only('email', 'password');

        //Attempt to verify the credentials and create a token for the user
        try{
            if(! $token = JWTAuth::attempt([$emailOrPhone => $request->email, 'password' => $request->password])){
                return ['status' => false, 'message' => 'Incorrect login credentials', 'code' => 400];
            }
        }catch(JWTException $e){
            Log::error($e->getMessage());
            return ['status' => false, 'message' => 'Could not create token', 'code' => 500];
        }
        $user = JWTAuth::user();
        $status = true;
        return compact('status', 'token', 'user');
    }

    public function securityAgentLogin($request){
        $result = $this->login($request);
        if(!$result['status']) {
            return $result;
        }

        if(!in_array(UserType::SECURITY, getUserTypes($result['user']))){
            Auth::logout();
            return ['status' => false, 'message' => 'Unauthorized access', 'code' => 400];
        }
        return $result;
    }

    public function logout(){
        try{
            $user = JWTAuth::parseToken()->authenticate();
            JWTAuth::invalidate(JWTAuth::getToken());
        }
        catch(JWTException $e){
            return response()->json(['error' => 'Token is invalid or expired'], 400);
        }
        return response()->json(['message' => 'Logout successful']);
    }
}
