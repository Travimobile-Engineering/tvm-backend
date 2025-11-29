<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    protected $frontendBaseUrl;

    protected $mobileRedirectUri;

    public function __construct()
    {
        if (app()->environment('production')) {
            $this->frontendBaseUrl = config('services.frontend_baseurl');
            $this->mobileRedirectUri = config('services.mobile_redirect_uri'); // Mobile deep link
        } else {
            $this->frontendBaseUrl = config('services.staging_frontend_baseurl');
            $this->mobileRedirectUri = config('services.staging_mobile_redirect_uri'); // Staging mobile deep link
        }
    }

    /**
     * Redirect to Google OAuth (Handles Web & Mobile).
     */
    public function redirectToGoogle(Request $request)
    {
        $authUrl = Socialite::driver('google')->stateless()->redirect()->getTargetUrl();

        if ($request->has('mobile')) {
            return response()->json([
                'authorization_url' => $authUrl,
            ]);
        }

        return redirect($authUrl);
    }

    /**
     * Handle Google OAuth callback (Works for Web & Mobile).
     */
    public function handleCallback(Request $request)
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();

            $fullName = $googleUser->name;
            $nameParts = explode(' ', $fullName, 2);
            $firstName = $nameParts[0] ?? '';
            $lastName = $nameParts[1] ?? '';

            $user = User::where('email', $googleUser->getEmail())->first();

            if (! $user) {
                $user = User::create([
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $googleUser->email,
                    'password' => bcrypt(uniqid()),
                    'email_verified_at' => now(),
                    'email_verified' => 1,
                    'is_admin_approve' => 1,
                ]);
            } else {
                $user->update([
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                ]);
            }

            $user->tokens()->delete();
            $token = $user->createToken('auth-token')->plainTextToken;

            if ($request->has('mobile')) {
                return response()->json([
                    'token' => $token,
                    'user' => $user,
                ]);
            }

            $redirectUrl = $request->has('mobile')
                ? $this->mobileRedirectUri
                : $this->frontendBaseUrl;

            return redirect($redirectUrl.'/auth/callback?'.http_build_query([
                'token' => $token,
                'user' => $user,
            ]));

        } catch (\Exception $e) {
            if ($request->has('mobile')) {
                return response()->json([
                    'error' => 'Authentication failed: '.$e->getMessage(),
                ], 401);
            }

            return redirect($this->frontendBaseUrl.'/auth/callback?'.http_build_query([
                'error' => 'Authentication failed: '.$e->getMessage(),
            ]));
        }
    }
}
