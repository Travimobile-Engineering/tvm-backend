<?php

namespace App\Services\Auth;

use App\Enum\MailingEnum;
use App\Enum\UserType;
use App\Models\User;
use App\Trait\HttpResponse;

class AuthService
{
    use HttpResponse;

    public function agentSignUp($request)
    {
        $fullName = trim($request->input('name'));
        $nameParts = explode(' ', $fullName, 2);

        $firstName = $nameParts[0] ?? null;
        $lastName = $nameParts[1] ?? null;

        $contact = trim($request->input('contact'));
        $isEmail = filter_var($contact, FILTER_VALIDATE_EMAIL);

        $email = $isEmail ? $contact : null;
        $phone = !$isEmail ? $contact : null;

        $existingUser = User::where(function ($query) use ($email, $phone) {
                if ($email) {
                    $query->where('email', $email);
                }
                if ($phone) {
                    $query->orWhere('phone_number', $phone);
                }
            })->first();

        if ($existingUser) {
            return $this->error(null, "Email or phone number already in use.", 400);
        }

        $code = generateUniqueNumber('users', 'verification_code', 5);

        $user = User::create([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'phone_number' => $phone,
            'verification_code' => $code,
            'verification_code_expires_at' => now()->addMinutes(10),
            'user_category' => [UserType::AGENT],
            'password' => bcrypt($request->password),
        ]);

        if ($email) {
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

        return $this->success(null, "User created successfully", 201);
    }

    public function verifyAcount($request)
    {
        $user = User::where('verification_code', $request->code)
            ->whereFuture('verification_code_expires_at')
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
}

