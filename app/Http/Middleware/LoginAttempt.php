<?php

namespace App\Http\Middleware;

use App\Enum\UserStatus;
use App\Models\User;
use App\Trait\HttpResponse;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;

class LoginAttempt
{
    use HttpResponse;

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $loginValue = !empty($request->input('email'))
            ? $request->input('email')
            : $request->input('phone_number');

        $key = "failed_attempts_{$loginValue}";

        $loginField = filter_var($loginValue, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone_number';

        if ($loginField === 'phone_number' && !empty($loginValue)) {
            $loginValue = formatPhoneNumber($loginValue);
        }

        $credentials = [
            $loginField => $loginValue,
            'password' => $request->input('password'),
        ];

        $user = User::where($loginField, $loginValue)->first();

        if (!$user) {
            return $this->error(null, "User doesn't exist", 404);
        }

        if ($user->status->isBlocked() && $user->reason === UserStatus::FAILED_LOGIN_ATTEMPTS->value) {
            $data = [
                'status' => UserStatus::BLOCKED->value,
                'reason' => UserStatus::FAILED_LOGIN_ATTEMPTS->value,
            ];
            return $this->error($data, 'Your account has been blocked due to too many failed attempts.', 403);
        }

        if (!JWTAuth::attempt($credentials)) {
            $attempts = Cache::get($key, 0) + 1;
            Cache::put($key, $attempts, now()->addMinutes(30));

            if ($attempts >= 5) {
                if ($user) {
                    $user->status = UserStatus::BLOCKED->value;
                    $user->reason = UserStatus::FAILED_LOGIN_ATTEMPTS->value;
                    $user->save();
                }
                Cache::forget($key);
                $data = [
                    'status' => UserStatus::BLOCKED->value,
                    'reason' => UserStatus::FAILED_LOGIN_ATTEMPTS->value,
                ];
                return $this->error($data, 'Your account has been blocked due to too many failed attempts.', 403);
            }

            return $this->error(null, "Invalid credentials", 401);
        }

        Cache::forget($key);

        return $next($request);
    }
}
