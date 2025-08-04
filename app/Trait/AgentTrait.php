<?php

namespace App\Trait;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;

trait AgentTrait
{
    protected function validatePassword($user, $request)
    {
        $request->validate([
            'password' => ['required', 'string'],
        ]);

        $userKey = 'login_attempts:' . $user->id;
        $blockKey = 'login_blocked:' . $user->id;

        if (Cache::has($blockKey)) {
            return $this->error(null, "Too many attempts. Try again later.", 429);
        }

        if (Hash::check($request->password, $user->password)) {
            Cache::forget($userKey);
            return $this->success(null, "Valid credentials");
        }

        $attempts = Cache::increment($userKey);

        if ($attempts === 1) {
            Cache::put($userKey, 1, now()->addMinutes(5));
        }

        if ($attempts >= 3) {
            Cache::put($blockKey, now()->addMinutes(10)->timestamp, 600);
            Cache::forget($userKey);
            return $this->error(null, "Too many attempts. You are blocked", 429);
        }

        return $this->error(null, "Invalid credentials", 400);
    }
}
