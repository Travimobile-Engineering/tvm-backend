<?php

namespace App\Http\Controllers\Auth;

use Carbon\Carbon;
use App\Models\User;
use App\Trait\HttpResponse;
use Illuminate\Http\Request;
use App\Mail\ConfirmationEmail;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;

class ForgotPasswordController extends Controller
{
    use HttpResponse;

    protected $user;
    protected $otp;
    protected $password;

    public function __construct(Request $request){
        $this->user = User::firstWhere('email', $request->email);
        $this->otp = $request->otp ?? str_pad(mt_rand(11111, 99999), 5, 0);
        $this->password = $request->password;
    }

    public function store_otp(){
        $this->user->verification_code = $this->otp;
        $this->user->verification_code_expires_at = now()->addMinutes(10);
        $this->user->save();
    }

    public function send_password_reset_otp(){
        $this->store_otp();
        $name = $this->user->first_name.' '.$this->user->last_name;
        Mail::to($this->user->email)->send(new ConfirmationEmail($name, $this->otp, 'email.password_reset_otp'));
        return $this->success(null, 'Password reset OTP has been sent to your email');
    }

    public function verify_otp()
    {
        if($this->otp != $this->user?->verification_code) {
            return ['message'=>'Invalid OTP or Email address', 'status'=>false];
        }

        if($this->user->verification_code_expires_at < now()) {
            return ['message'=>'The verification code has expired. Please request for a new OTP', 'status'=>false];
        }

        return ['message'=>'OTP is correct', 'status'=>true];
    }

    public function verify_password_reset_otp()
    {
        $verify = $this->verify_otp();

        if(!$verify['status']) {
            return $this->error(null, $verify['message']);
        }

        return $this->success(null, $verify['message']);
    }

    public function resetPassword()
    {
        $verify = $this->verify_otp();

        if(!$verify['status']) {
            return $this->error(null, $verify['message']);
        }

        if(is_null($this->password)) {
            return $this->error(null, 'Password cannot be null');
        }

        $this->user->password = Hash::make($this->password);
        $this->user->verification_code = "";
        $this->user->verification_code_expires_at = null;
        $this->user->save();
        return $this->success(null, 'User password updated successfully');
    }

    // public function resetPassword(Request $request){

    //     $validation = $request->validate([
        //         'email' => 'required|email|exists:users,email',
    //         'password' => 'required|min:8|confirmed',
    //         'otp' => 'required',
    //     ]);

    //     $response = Password::broker()->reset($validation, function($user, $password){
    //         $user->password = Hash::make($password);
    //         $user->save();
    //     });

    //     if($response === Password::PASSWORD_RESET) return $this->success(null, 'User password updated successfully');
    //     return response()->json(['error' => trans($response)]);

    // }

    // public function send_password_reset_link(Request $request){

    //     $response = Password::sendResetLink($request->only('email'));

    //     if ($response == Password::RESET_LINK_SENT) {
    //         return response()->json(['message' => 'Password reset link sent to your email.'], 200);
    //     } elseif ($response == Password::INVALID_USER) {
    //         return response()->json(['error' => 'No user found with that email address.'], 422);
    //     } else {
    //         // If there is an unexpected error, return a generic message
    //         return response()->json(['error' => trans($response)], 500);
    //     }
    // }
}
