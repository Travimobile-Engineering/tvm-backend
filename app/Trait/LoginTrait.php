<?php

namespace App\Trait;

use App\Enum\UserStatus;
use App\Enum\UserType;
use Illuminate\Http\JsonResponse;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

trait LoginTrait
{
    use HttpResponse, DriverTrait;

    public function authUserLogin($request, array $allowedCategories): JsonResponse
    {
        $loginValue = $request->email ?? $request->phone_number;
        $loginField = filter_var($loginValue, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone_number';

        $credentials = [
            $loginField => $loginValue,
            'password' => $request->password,
        ];

        try {
            if (JWTAuth::attempt($credentials)) {
                $user = JWTAuth::user();

                if (!in_array($user->user_category, $allowedCategories)) {
                    return $this->error(null, 'Unauthorized access.', 403);
                }

                if ($res = $this->authCheck($user)) {
                    return $res;
                }

                if ($user->wallet > 0) {
                    $this->userIncrementBalance($user, $user->wallet);
                    $user->update([
                        'wallet' => 0,
                    ]);
                }

                $token = JWTAuth::fromUser($user);

                $response = array_merge([
                    'token' => $token,
                    'user' => $user,
                ], $this->additionalData($user));

                return $this->success($response, 'Login successful');
            }

            return $this->error(null, 'Credentials do not match', 401);

        } catch (JWTException $e) {
            return $this->error(null, 'An error occurred: ' . $e->getMessage(), 500);
        }
    }

    protected function authCheck($user)
    {
        if (! $user->email_verified) {
            return $this->error(null, 'Email has not been verified!', 400);
        }

        if ($user->status === null && !in_array($user->status, UserStatus::cases())) {
            return $this->error(null, 'Account status is unknown!', 400);
        }

        if ($user->status->isInactive()) {
            return $this->error(null, 'Account is inactive!', 400);
        }

        if ($user->status->isBlocked()) {
            return $this->error(null, 'Account is blocked!', 400);
        }

        if ($user->status->isPending()) {
            return $this->error(null, 'Account is pending!', 400);
        }

        if ($user->status->isDeleted()) {
            return $this->error(null, 'Account is deleted!', 400);
        }
    }

    protected function additionalData($user): array
    {
        return match($user->user_category) {
            UserType::SECURITY->value => [],
            UserType::AGENT->value => [],
            UserType::PASSENGER->value => [],
            UserType::DRIVER->value => [],
            default => [],
        };
    }
}
