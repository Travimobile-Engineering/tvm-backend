<?php

namespace App\Http\Controllers\auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class VerifyController extends Controller
{
    public function index(Request $request){

        try{
            $validation = $request->validate([
                'email' => 'required|exists:users,email',
                'verification_code' => 'required|numeric|digits:5'
            ]);
        }
        catch(ValidationException $e){
            return response()->json(['errors' => $e->errors()]);
        }

        $user = User::where([
            'email' => $validation['email'],
            'verification_code' => $validation['verification_code']
        ])->first();

        if($user){
            if($user->verification_code_expires_at > Carbon::now()){
                $user->email_verified = 1;
                $user->email_verified_at = Carbon::now();
                $user->save();
                return response()->json(['message' => 'User account verified successfully']);
            }
            else return response()->json(['error' => 'Verification code has expired']);
        }
        else return response()->json(['error' => 'Invalid email or verification code']);

    }
}
