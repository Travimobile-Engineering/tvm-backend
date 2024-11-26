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
                'email' => 'string|email|unique:users,email|max:255',
                'phone_number' => 'required|unique:users,phone_number|numeric',
                'password' => 'required|string|min:8|confirmed',
                'address' => 'string|max:255',
                'nin' => 'string'
            ]);

        }catch(ValidationException $e){
            return response()->json(['error' => array("message" => collect($e->errors())->flatten()->first())], 400);
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

        // Get the first name and last name
        $names = explode(' ', $validation['full_name'], 2);

        $category = [1];
        if(isset($request->user_category) && $request->user_category == 2){
            $category[] = 2;

            try{
                $request->validate([
                    'address' => 'required',
                    'email' => 'required',
                    'phone_number' => 'required',
                    'nin' => 'required',
                ]);
    
            }catch(ValidationException $e){
                return response()->json(['error' => array("message" => collect($e->errors())->flatten()->first())], 400);
            }
        } 
        
        $user = User::create([
            'email' => $validation['email'],
            'phone_number' => $validation['phone_number'],
            'first_name' => $names[0],
            'last_name' => $names[1] ?? "",
            'password' => Hash::make($validation['password']),
            'user_category' => json_encode($category),
            'verification_code' => $verification_code,
            'verification_code_expires_at' => Carbon::now()->addMinutes(10),
            'uuid' => $uuid,
            'address' => $validation['address'] ?? "",
            'nin' => $validation['nin'] ?? "",

        ]);

        if($user) {
            Mail::to($validation['email'])->send(new ConfirmationEmail($user, $verification_code));
            return response()->json(['Message' => 'User created successfully'], 201);
        }
        else return response()->json(['Error' => 'Failed to create user'], 500);
    }
}
