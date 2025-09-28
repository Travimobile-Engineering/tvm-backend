<?php

namespace App\Services\Auth;

use App\Enum\UserStatus;
use App\Mail\ConfirmationEmail;
use App\Models\User;
use App\Trait\HttpResponse;

class ForgotPasswordService
{
    use HttpResponse;

    public function sendPasswordResetOtp($request)
    {
        $value = $request->email;
        $field = filter_var($value, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone_number';

        if ($field === 'phone_number') {
            $value = formatPhoneNumber($value);
            $this->validatePhone($value);
        } else {
            $this->validateEmail($request);
        }

        $user = User::where($field, $value)->firstOrFail();
        $code = getCode();

        $user->update([
            'verification_code' => $code,
            'verification_code_expires_at' => now()->addMinutes(10),
        ]);

        if ($field === 'phone_number') {
            sendSmS($value, "Your Travi password reset OTP is: $code. Valid for 10 mins. Do not share with anyone. Powered By Travi");
        } else {

            $name = "{$user->first_name} {$user->last_name}";
            sendMail(
                $user->email,
                new ConfirmationEmail($name, $code, 'email.password_reset_otp')
            );
        }

        return $this->success(null, 'Password reset OTP has been sent!');
    }

    public function verifyPasswordResetOtp($request)
    {
        $verify = User::where('verification_code', $request->otp)
            ->where('verification_code_expires_at', '>', now())
            ->first();

        if (! $verify) {
            return $this->error(null, 'Invalid code or expired.', 400);
        }

        return $this->success(null, 'OTP is correct');
    }

    public function resetPassword($request)
    {
        $value = $request->email;
        $field = filter_var($value, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone_number';

        if ($field === 'phone_number') {
            $value = formatPhoneNumber($value);
        }

        $user = User::where($field, $value)
            ->where('verification_code', $request->otp)
            ->where('verification_code_expires_at', '>', now())
            ->first();

        if (! $user) {
            return $this->error(null, 'Invalid code or expired time.', 404);
        }

        $user->update([
            'email_verified' => 1,
            'email_verified_at' => now(),
            'password' => bcrypt($request->password),
            'verification_code' => 0,
            'verification_code_expires_at' => null,
            'status' => UserStatus::ACTIVE->value,
        ]);

        return $this->success(null, 'User password updated successfully');
    }

    private function validateEmail($request)
    {
        $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
        ]);
    }

    private function validatePhone(string $value)
    {
        $exists = User::where('phone_number', $value)->exists();

        if (! $exists) {
            return $this->error(null, 'Phone number not found.', 422);
        }
    }
}
