<?php

namespace App\Http\Controllers\auth;

use App\Http\Controllers\Controller;
use App\Mail\ConfirmationEmail;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class RegisterController extends Controller
{
    //method to register a new user
    public function signup(Request $request){

        try{
            $validation = $request->validate([
                'full_name' => 'required|string|max:255',
                'email' => 'required|string|email|unique:users,email|max:255',
                'phone_number' => 'required|string|unique:users,phone_number|max:255',
                'password' => 'required|string|min:8|confirmed',
            ]);

        }catch(ValidationException $e){
            return response()->json(['errors' => $e->errors()], 422);
        }

        $verification_code = str_pad(rand(0, 99999), 5, 0, STR_PAD_LEFT);
        do{ 
            $uuid = (String) time();
            $randomNumber = '';
            $remainingDigits = 16 - strlen($uuid);
            for($i=0; $i< $remainingDigits; $i++){
                $randomNumber .= mt_rand(0, 9);
            }
            $uuid = $randomNumber . $uuid;
        }
        while(User::where('uuid', $uuid)->exists());

        $names = explode(' ', $validation['full_name'], 2);

        $user = User::create([
            'email' => $validation['email'],
            'phone_number' => $validation['phone_number'],
            'first_name' => $names[0],
            'last_name' => $names[1] ?? "",
            'password' => Hash::make($validation['password']),
            'verification_code' => $verification_code,
            'verification_code_expires_at' => Carbon::now()->addMinutes(10),
            'uuid' => $uuid,
        ]);

        if($user) {
            Mail::to($validation['email'])->send(new ConfirmationEmail($user, $verification_code));
            return response()->json(['Message' => 'User created successfully'], 201);
        }
        else return response()->json(['Error' => 'Failed to create user'], 500);
    }
}
