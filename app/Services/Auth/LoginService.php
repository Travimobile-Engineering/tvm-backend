<?php

namespace App\Services\Auth;

use App\Enum\UserType;
use App\Models\Agent;
use App\Trait\HttpResponse;
use App\Trait\LoginTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class LoginService
{
    use HttpResponse, LoginTrait;

    public function login($request): array
    {
        $emailOrPhone = filter_var($request->email, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone_number';
        $credentials = $request->only('email', 'password');

        // Attempt to verify the credentials and create a token for the user
        try {
            if (! $token = JWTAuth::attempt([$emailOrPhone => $request->email, 'password' => $request->password])) {
                return ['status' => false, 'message' => 'Incorrect login credentials', 'code' => 400];
            }
        } catch (JWTException $e) {
            Log::error($e->getMessage());

            return ['status' => false, 'message' => 'Could not create token', 'code' => 500];
        }
        $user = JWTAuth::user();
        $status = true;

        return compact('status', 'token', 'user');
    }

    public function authLogin($request): JsonResponse
    {
        return $this->authUserLogin($request, UserType::group(UserType::appUsers()));
    }

    public function agencyLogin($request): JsonResponse
    {
        return $this->authUserLogin($request, UserType::group(UserType::agencyUsers()));
    }

    public function securityAgentLogin($request)
    {
        $result = $this->login($request);
        if (! $result['status']) {
            return $result;
        }

        if (! in_array(UserType::SECURITY->value, getUserTypes($result['user']))) {
            Auth::logout();

            return ['status' => false, 'message' => 'Unauthorized access', 'code' => 400];
        }

        return $result;
    }

    public function agentLogin($request)
    {
        $credentials = [
            'email' => $request->email,
            'password' => $request->password,
        ];

        try {
            $token = Auth::guard('agent')->attempt($credentials);

            if (! $token) {
                return $this->error(null, 'Credentials do not match', 401);
            }

            $agent = Auth::guard('agent')->user();

            return $this->success([
                'token' => $token,
                'user' => $agent?->load('states:id,name'),
            ], 'Login successful');

        } catch (JWTException $e) {
            return $this->error(null, 'An error occurred: '.$e->getMessage(), 500);
        }
    }

    public function updateData($request)
    {
        $user = Agent::where('email', $request->email)->first();

        if (! $user) {
            return $this->error(null, 'User not found', 404);
        }

        $allowed = ['is_default_password', 'password']; // extend as needed

        $data = $request->only($allowed);

        if (empty($data)) {
            return $this->error(null, 'No valid fields provided to update', 400);
        }

        $user->update($data);

        return $this->success(null, 'Updated successfully');
    }

    public function logout()
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            JWTAuth::invalidate(JWTAuth::getToken());
        } catch (JWTException $e) {
            return response()->json(['error' => 'Token is invalid or expired'], 400);
        }

        return response()->json(['message' => 'Logout successful']);
    }
}
