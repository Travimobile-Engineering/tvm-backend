<?php

namespace App\Trait;

use Illuminate\Support\Facades\Hash;

trait AgentTrait
{
    protected function validatePassword($user, $request)
    {
        $request->validate([
            'password' => ['required', 'string'],
        ]);

        if (Hash::check($request->password, $user->password)) {
            return $this->success(null, "Password is valid");
        }

        return $this->error(null, "Invalid password", 400);
    }
}
