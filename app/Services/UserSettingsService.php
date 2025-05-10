<?php

namespace App\Services;

use App\Enum\MailingEnum;
use App\Enum\UserStatus;
use App\Mail\ConfirmationEmail;
use App\Models\SecurityQuestion;
use App\Models\User;
use App\Trait\HttpResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

class UserSettingsService
{
    use HttpResponse;

    public function getQuestions()
    {
        $data = SecurityQuestion::select('id', 'question')->get();
        return $this->success($data, 'Questions retrieved successfully');
    }

    public function setSecurityAnswer($request)
    {
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return $this->error(null, 'User not found', 404);
        }

        $normalizedAnswer = strtolower(trim($request->answer));
        $code = generateUniqueNumber('users', 'verification_code', 5);

        $user->update([
            'security_question_id' => $request->security_question_id,
            'security_answer' => Hash::make($normalizedAnswer),
            'verification_code' => $code,
            'verification_code_expires_at' => now()->addMinutes(10),
        ]);

        Cache::put("security_reset_{$user->email}", true, now()->addMinutes(10));

        $data = [
            'name' => $user->first_name,
            'verification_code' => $code
        ];

        mailSend(
            MailingEnum::VERIFY_OTP,
            $user,
            "Verify Account",
            ConfirmationEmail::class,
            $data
        );

        return $this->success(null, 'Security answer set successfully');
    }

    public function createPassword($request)
    {
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return $this->error(null, 'User not found', 404);
        }

        if (!Cache::get("security_reset_{$user->email}")) {
            return $this->error(null, 'You must complete the security setup or answer first.', 403);
        }

        if (!$user->security_question_id || !$user->security_answer) {
            return $this->error(null, 'Please set your security question first', 403);
        }

        $user->update([
            'password' => Hash::make($request->password),
            'status' => UserStatus::ACTIVE,
            'reason' => null,
        ]);

        Cache::forget("security_reset_{$user->email}");

        $token = JWTAuth::fromUser($user);

        $data = [
            'token' => $token,
            'user' => $user
        ];

        return $this->success($data, 'Password created successfully');
    }

    public function changeSecurityAnswer($request)
    {
        $user = User::findOrFail($request->user_id);

        if (!Hash::check($request->password, $user->password)) {
            return $this->error(null, 'Incorrect password', 400);
        }

        $normalizedAnswer = strtolower(trim($request->answer));

        $user->update([
            'security_question_id' => $request->security_question_id,
            'security_answer' => Hash::make($normalizedAnswer)
        ]);

        return $this->success(null, 'Security answer set successfully');
    }

    public function getUserQuestion()
    {
        $email = request()->query('email');

        if (!$email) {
            return $this->error(null, 'Email is required', 400);
        }

        $user = User::where('email', $email)->first();

        if (!$user) {
            return $this->error(null, 'User not found', 404);
        }

        if (!$user->security_question_id) {
            return $this->error(null, 'No security question set for this user', 400);
        }

        $data = [
            'question' => $user->securityQuestion->question
        ];

        return $this->success($data, 'Security question retrieved successfully');
    }

    public function verifySecurityAnswer($request)
    {
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return $this->error(null, 'User not found', 404);
        }

        if (!$user->security_question_id || !$user->security_answer) {
            return $this->error(null, 'Please set your security question first', 403);
        }

        $normalizedInput = strtolower(trim($request->answer));

        if (!Hash::check($normalizedInput, $user->security_answer)) {
            return $this->error(null, 'Incorrect answer', 400);
        }

        Cache::put("security_reset_{$user->email}", true, now()->addMinutes(10));

        return $this->success(null, 'Security answer verified successfully');
    }
}

