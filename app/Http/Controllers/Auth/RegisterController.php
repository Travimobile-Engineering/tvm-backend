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
                'email' => 'nullable|string|email|max:255',
                'phone_number' => 'required|numeric',
                'password' => 'required|string|min:8',
                'address' => 'nullable|string|max:255',
                'nin' => 'nullable|string',
                'verification_code' => 'nullable|numeric',
                'user_category' => 'nullable|int',
            ]);

        }catch(ValidationException $e){
            return response()->json(['error' => array("message" => collect($e->errors())->flatten()->first())], 400);
        }
        
        do $verification_code = str_pad(rand(0, 99999), 5, 0, STR_PAD_RIGHT);
        while(strlen($verification_code) < 5);

        $user = User::where('phone_number', $request->phone_number)->first();
        if($user && $user->email_verified == 1) return response()->json(['error' => 'Phone number already exist'], status: 400);

        $user = User::where('email', $request->email)->first();
        if($user){
            if($user->email_verified == 1) return response()->json(['error' => 'Email address already exist'], status: 400);
            elseif(!isset($request->verification_code) || empty($request->verification_code)){
                $this->send_verification_code($request, false, $verification_code);
                return response()->json(['Message' => 'User created successfully'], 200);
            }
        }

        
        if(isset($request->verification_code) && !empty($request->verification_code)){
            
            $response = $this->verify_account($request);
            if($response['status'] == false) return response()->json(['error' => $response['error']], 400);
            
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
            $user = User::where('email', $request->email)->update([
                'phone_number' => $validation['phone_number'],
                'first_name' => $names[0],
                'last_name' => $names[1] ?? "",
                'password' => Hash::make($validation['password']),
                'user_category' => json_encode(value: $category),
                'uuid' => $uuid,
                'address' => $validation['address'] ?? "",
                'nin' => $validation['nin'] ?? "",
            ]);

            if($user) return response()->json(['message' => 'Account verified successfully'], 200);
            else return response()->json(['error' => 'Ooops! An error occured. Please try again'], 400);
        }
        
        $user = User::create([
            'email' => $validation['email'] ?? "",
            'phone_number' => $validation['phone_number'],
            'verification_code' => $verification_code,
            'verification_code_expires_at' => Carbon::now()->addMinutes(10)
        ]);

        if($user) {
            $this->send_verification_code($request, false, $verification_code);
            return response()->json(['Message' => 'Account created successfully'], 200);
        }
        else return response()->json(['error' => 'Failed to create user'], 400);
    }

    public function send_verification_code(
        Request $request,
        bool $returnResponse = true,
        int $verification_code = null
    )
    {

        $email = $request->email;

        
        if(!empty($email)){
            
            $user = User::where('email', $email)->first();
            if($user){

                if(empty($verification_code)){

                    $verification_code = str_pad(rand(0, 99999), 5, 0, STR_PAD_LEFT);
                    $user->verification_code = $verification_code;
                    $user->verification_code_expires_at = Carbon::now()->addMinutes(10);
                    $user->save();
                }

                $name = $user->first_name.' '.$user->last_name;

                Mail::to($email)->send(new ConfirmationEmail($name, $verification_code));
                
                if($returnResponse)
                return response()->json(['Message' => 'Verification code sent to your email address'], 200);
            }
            else return response()->json(['error' => 'User not found'], 400);
            

        }
        
    }

    public function verify_account(Request $request){
        try{
            $validation = $request->validate([
                'email' => 'required|exists:users,email',
                'verification_code' => 'required|numeric|digits:5'
            ]);

            $user = User::where([
                'email' => $validation['email'],
                'verification_code' => $validation['verification_code']
            ])->first();
    
            if($user){
                if($user->verification_code_expires_at > Carbon::now()){
                    $user->email_verified = 1;
                    $user->email_verified_at = Carbon::now();
                    $user->save();
                    return ['status' => true, 'message' => 'User account verified successfully'];
                }
                else return ['status' => false, 'error' => 'Verification code has expired'];
            }
            else return ['status' => false, 'error' => 'Invalid email or verification code'];
        }
        catch(ValidationException $e){
            return ['status' => false, 'error' => collect($e->errors())->flatten()->first()];
        }

    }
}
