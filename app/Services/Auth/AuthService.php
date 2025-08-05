<?php

namespace App\Services\Auth;

use App\Enum\UserStatus;
use App\Models\User;
use App\Contracts\SMS;
use App\Enum\MailingEnum;
use App\Enum\UserType;
use App\Trait\HttpResponse;
use App\Mail\ConfirmationEmail;

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
            $this->validateUser($existingUser, $request);
            $this->sendCode($request, $existingUser);

            return $this->success(null, "OTP has been resent to your email or phone number.");
        }

        $code = generateUniqueNumber('users', 'verification_code', 5);

        $createdBy = null;
        if ($request->filled('referral_code')) {
            $referrer = User::where('referral_code', $request->referral_code)->first();
            if ($referrer) {
                $createdBy = $referrer->id;
            } else {
                return $this->error(null, "Invalid referral code.", 400);
            }
        }

        $user = User::create([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $request->email,
            'phone_number' => $request->filled('phone_number') ? formatPhoneNumber($request->phone_number) : null,
            'verification_code' => $code,
            'created_by' => $createdBy,
            'verification_code_expires_at' => now()->addMinutes(10),
            'user_category' => $request->user_category,
            'password' => bcrypt($request->password),
            'status' => UserStatus::INACTIVE->value,
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
            'status' => UserStatus::ACTIVE->value,
        ]);

        return $this->success($user, "Account verified successfully");
    }

    public function resendCode($request)
    {
        $value = $request->email;
        $field = filter_var($value, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone_number';

        if ($field === 'phone_number') {
            $value = formatPhoneNumber($value);
            $this->validatePhone($value);
        } else {
            $this->validateEmail($request);
        }

        $user = User::where($field, $value)->first();

        if (! $user) {
            return $this->error(null, 'User not found', 404);
        }

        $this->customvalidate($request, $user, $field, $value);

        $code = generateUniqueNumber('users', 'verification_code', 5);

        $user->update([
            'verification_code' => $code,
            'verification_code_expires_at' => now()->addMinutes(10),
        ]);

        if ($field === 'phone_number') {
            sendSmS($value, "Your Travi verification OTP is: $code. Valid for 10 mins. Do not share with anyone. Powered By Travi");
        } else {
            $type = MailingEnum::RESEND_CODE;
            $subject = "Resend code";
            $mail_class = ConfirmationEmail::class;
            $data = [
                'name' => $user->first_name,
                'verification_code' => $code
            ];
            mailSend($type, $user, $subject, $mail_class, $data);
        }

        return $this->success(null, 'Verification code sent successfully');
    }

    public function createDriver($request)
    {
        $user = User::where([
                'id' => $request->agent_id,
                'user_category' => UserType::AGENT->value,
            ])
            ->first();

        if (!$user) {
            return $this->error(null, "Agent not found", 404);
        }

        $existingUser = $this->findUserByEmailOrPhone($request);

        if ($existingUser) {
            $this->validateUser($existingUser, $request);
            $this->sendCode($request, $existingUser);

            return $this->success(null, "OTP has been resent to your email or phone number.");
        }

        $code = generateUniqueNumber('users', 'verification_code', 5);

        $user = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'phone_number' => $request->filled('phone_number') ? formatPhoneNumber($request->phone_number) : null,
            'verification_code' => $code,
            'verification_code_expires_at' => now()->addMinutes(10),
            'user_category' => UserType::DRIVER->value,
            'password' => bcrypt($request->password),
            'created_by' => $request->agent_id ?? null,
            'status' => UserStatus::INACTIVE->value,
        ]);

        $this->sendCode($request, $user);

        return $this->success(null, "User created successfully", 201);
    }

    public function verifyDriverAccount($request)
    {
        $user = User::where('created_by', $request->agent_id)
            ->where('verification_code', $request->code)
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
            'status' => UserStatus::ACTIVE->value,
        ]);

        return $this->success($user, "Account verified successfully");
    }

    private function findUserByEmailOrPhone($request): ?User
    {
        $normalizedPhone = $request->filled('phone_number')
            ? formatPhoneNumber($request->phone_number)
            : null;

        return User::where(function ($query) use ($request, $normalizedPhone) {
            if ($request->filled('email')) {
                $query->where('email', $request->email);
            }

            if ($normalizedPhone) {
                $query->orWhere('phone_number', $normalizedPhone);
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
                $request,
                "Verify Account",
                "App\Mail\ConfirmationEmail",
                $data
            );
        }

        if ($request->filled('phone_number')) {
            $this->smsService->sendSms(
                formatPhoneNumber($request->phone_number),
                "Your Travi Verification Pin is: $code. Valid for 10 mins. Do not share with anyone. Powered By Travi"
            );
        }
    }

    private function hasActiveCode(User $user): bool
    {
        return $user->verification_code_expires_at &&
            !$user->email_verified &&
            $user->verification_code_expires_at >= now();
    }

    private function validateUser(User $existingUser, $request)
    {
        if ($this->hasActiveCode($existingUser)) {
            return $this->error(null, "A verification code has already been sent. Please check your email or phone.", 400);
        }

        if (
            $request->filled('email') &&
            $existingUser->email === $request->email &&
            $existingUser->email_verified
        ) {
            return $this->error(null, "Email address already in use.", 400);
        }

        if ($request->filled('phone_number')) {
            $normalized = formatPhoneNumber($request->phone_number);

            if ($existingUser->phone_number === $normalized) {
                return $this->error(null, "Phone number already in use.", 400);
            }
        }
    }

    private function validateEmail($request)
    {
        $request->validate([
            'email' => ['required', 'email', 'exists:users,email']
        ]);
    }

    private function validatePhone(string $value)
    {
        $exists = User::where('phone_number', $value)->exists();

        if (! $exists) {
            return $this->error(null, 'Phone number not found.', 422);
        }
    }

    private function customvalidate($request, $user, $field, $value)
    {
        if ($field === 'email') {
            if ($user->email !== $request->email) {
                return $this->error(null, 'Email mismatch.', 400);
            }

            if ($user->email_verified) {
                return $this->error(null, 'Email already verified.', 400);
            }
        } else {
            if ($user->phone_number !== $value) {
                return $this->error(null, 'Phone number mismatch.', 400);
            }
        }
    }
}

