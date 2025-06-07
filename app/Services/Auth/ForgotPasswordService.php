<?php

namespace App\Services\Auth;

use App\Mail\ConfirmationEmail;
use App\Trait\HttpResponse;
use User;

class ForgotPasswordService
{
    use HttpResponse;

    public function sendPasswordResetOtp($request)
    {
        $user = User::where('email', $request->email)->firstOrFail();
        $code = getCode();

        $user->update([
            'verification_code' => $code,
            'verification_code_expires_at' => now()->addMinutes(10)
        ]);

        $name = "{$user->first_name} {$user->last_name}";

        sendMail(
            $user->email,
            new ConfirmationEmail($name, $code, 'email.password_reset_otp')
        );

        return $this->success(null, 'Password reset OTP has been sent to your email');
    }
    public function verifyPasswordResetOtp($request)
    {
        $verify = User::where('verification_code', $request->otp)
            ->where('verification_code_expires_at', '>', now())
            ->first();

        if (! $verify) {
            return $this->error(null, "Invalid code or expired.", 400);
        }

        return $this->success(null, "OTP is correct");
    }

    public function resetPassword($request)
    {
        $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
            'otp' => ['required', 'string', 'max:5'],
            'password' => ['required', 'string', 'min:8']
        ]);

        $user = User::where('email', $request->email)
            ->where('verification_code', $request->otp)
            ->where('verification_code_expires_at', '>', now())
            ->first();

        if (! $user) {
            return $this->error(null, "Invalid code or expired time.", 404);
        }

        $user->update([
            'password' => bcrypt($request->password),
            'verification_code' => 0,
            'verification_code_expires_at' => null,
        ]);

        return $this->success(null, 'User password updated successfully');
    }
}

