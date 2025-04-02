<?php

namespace App\Services\Auth;

use App\Contracts\SMS;
use App\Enum\MailingEnum;
use App\Mail\ConfirmationEmail;
use App\Models\User;
use App\Trait\HttpResponse;

class AuthService
{
    use HttpResponse;

    public function __construct(
        protected SMS $smsService
    )
    {}

    public function accountSignUp($request)
    {
        $fullName = trim($request->input('full_name'));
        $nameParts = explode(' ', $fullName, 2);

        $firstName = $nameParts[0] ?? null;
        $lastName = $nameParts[1] ?? null;

        $existingUser = $this->findUserByEmailOrPhone($request);

        if ($existingUser) {
            if ($this->hasActiveCode($existingUser)) {
                return $this->error(null, "A verification code has already been sent. Please check your email or phone.", 400);
            }

            if ($existingUser->email_verified) {
                return $this->error(null, "Email or phone number already in use.", 400);
            }

            $this->sendCode($request, $existingUser);

            return $this->success(null, "OTP has been resent to your email or phone number.");
        }

        $code = generateUniqueNumber('users', 'verification_code', 5);

        $user = User::create([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $request->email,
            'phone_number' => $request->phone_number,
            'verification_code' => $code,
            'verification_code_expires_at' => now()->addMinutes(10),
            'user_category' => $request->user_category,
            'password' => bcrypt($request->password),
        ]);

        $this->sendCode($request, $user);

        return $this->success(null, "User created successfully", 201);
    }

    public function verifyAcount($request)
    {
        $user = User::where('verification_code', $request->code)
            ->where('verification_code_expires_at', '>=', now())
            ->first();

        if (! $user) {
            return $this->error(null, "Invalid code!", 400);
        }

        $user->update([
            'email_verified' => 1,
            'email_verified_at' => now(),
            'verification_code' => 0,
            'verification_code_expires_at' => null,
        ]);

        return $this->success($user, "Account verified successfully");
    }

    public function resendCode($request)
    {
        $user = User::where('email', $request->email)
            ->first();

        if (! $user) {
            return $this->error(null, 'User not found', 404);
        }

        if ($user->verification_code !== 0 || ($user->verification_code_expires_at !== null && $user->verification_code_expires_at >= now())) {
            return $this->error(null, "A verification code has already been sent. Please check your email.", 400);
        }

        $code = generateUniqueNumber('users', 'verification_code', 5);

        $user->update([
            'verification_code' => $code,
            'verification_code_expires_at' => now()->addMinutes(10),
        ]);

        $type = MailingEnum::RESEND_CODE;
        $subject = "Resend code";
        $mail_class = ConfirmationEmail::class;
        $data = [
            'name' => $user->first_name,
            'verification_code' => $code
        ];
        mailSend($type, $user, $subject, $mail_class, $data);

        return $this->success(null, 'Verification code sent successfully');
    }

    private function findUserByEmailOrPhone($request)
    {
        return User::where(function ($query) use ($request) {
            if ($request->filled('email')) {
                $query->where('email', $request->email);
            }

            if ($request->filled('phone_number')) {
                $query->orWhere('phone_number', $request->phone_number);
            }
        })->first();
    }

    private function sendCode($request, $user)
    {
        $code = generateUniqueNumber('users', 'verification_code', 5);

        $user->update([
            'verification_code' => $code,
            'verification_code_expires_at' => now()->addMinutes(10),
        ]);

        if ($request->filled('email')) {
            $data = [
                'name' => $user->first_name,
                'verification_code' => $code
            ];
            mailSend(
                MailingEnum::SIGN_UP_OTP,
                $user,
                "Verify Account",
                "App\Mail\ConfirmationEmail",
                $data
            );
        }

        if ($request->filled('phone_number')) {
            $this->smsService->sendSms(formatPhoneNumber($request->phone_number), "Your verification code is: $code");
        }
    }

    private function hasActiveCode(User $user): bool
    {
        return $user->verification_code_expires_at &&
            !$user->email_verified &&
            $user->verification_code_expires_at >= now();
    }
}

