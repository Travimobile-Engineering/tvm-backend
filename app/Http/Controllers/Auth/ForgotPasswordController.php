<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\Auth\ForgotPasswordService;

class ForgotPasswordController extends Controller
{
    public function __construct(
        protected ForgotPasswordService $forgotPasswordService
    )
    {}

    public function sendPasswordResetOtp(Request $request)
    {
        $request->validate([
            'email' => ['required', 'string']
        ]);

        return $this->forgotPasswordService->sendPasswordResetOtp($request);
    }
    public function verifyPasswordResetOtp(Request $request)
    {
        $request->validate([
            'otp' => ['required', 'max:5']
        ]);

        return $this->forgotPasswordService->verifyPasswordResetOtp($request);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => ['required', 'string'],
            'otp' => ['required', 'string', 'max:5'],
            'password' => ['required', 'string', 'min:8']
        ]);

        return $this->forgotPasswordService->resetPassword($request);
    }
}
