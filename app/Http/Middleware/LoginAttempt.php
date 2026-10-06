<?php

namespace App\Http\Middleware;

use App\Enum\UserStatus;
use App\Models\User;
use App\Trait\HttpResponse;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class LoginAttempt
{
    use HttpResponse;

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $loginValue = ! empty($request->input('email'))
            ? $request->input('email')
            : $request->input('phone_number');

        $key = "failed_attempts_{$loginValue}";

        $loginField = filter_var($loginValue, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone_number';

        if ($loginField === 'phone_number' && ! empty($loginValue)) {
            $loginValue = formatPhoneNumber($loginValue);
        }

        // Email and phone are encrypted at rest, so users are matched via their
        // deterministic blind index and the password is verified here.
        $user = $loginField === 'email'
            ? User::byEmail((string) $loginValue)->first()
            : User::byPhone((string) $loginValue)->first();

        if (! $user) {
            return $this->error(null, "User doesn't exist", 404);
        }

        if ($user->status->isBlocked() && $user->reason === UserStatus::FAILED_LOGIN_ATTEMPTS->value) {
            $data = [
                'status' => UserStatus::BLOCKED->value,
                'reason' => UserStatus::FAILED_LOGIN_ATTEMPTS->value,
            ];

            return $this->error($data, 'Your account has been blocked due to too many failed attempts.', 403);
        }

        if (! Hash::check((string) $request->input('password'), (string) $user->password)) {
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

            return $this->error(null, 'Invalid credentials', 401);
        }

        Cache::forget($key);

        return $next($request);
    }
}
