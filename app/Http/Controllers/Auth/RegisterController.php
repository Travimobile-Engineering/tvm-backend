<?php

namespace App\Http\Controllers\auth;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Mail\ConfirmationEmail;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Http\Requests\RegisterRequest;
use Illuminate\Validation\ValidationException;

class RegisterController extends Controller
{
    //method to register a new user
    public function signup(RegisterRequest $request){
        
        $category = ["1"];
        if(isset($request->user_category) && $request->user_category == 2){
            
            $agent_id = strtoupper(generateUniqueRandomString('users', 'agent_id', 12));
            $category[] = "2";


            $request->validate([
                'address' => 'required',
                // 'email' => 'required',
                // 'phone_number' => 'required',
                'nin' => 'required',
            ]);
        }

        $is_email = filter_var($request->contact, FILTER_VALIDATE_EMAIL);
        $email = !$is_email ? "" : $request->contact;
        $phone_number = !$is_email ? $request->contact : "";

        do $verification_code = str_pad(rand(0, 99999), 5, 0, STR_PAD_RIGHT);
        while(strlen($verification_code) < 5);

        if(!empty($phone_number)){
            $user = User::where('phone_number', $phone_number)->first();
            if($user && $user->email_verified == 1) return response()->json(['error' => 'Phone number already exist'], status: 400);
        }

        if(!empty($email)){
            $user = User::where('email', $email)->first();
            if($user && $user->email_verified == 1) return response()->json(['error' => 'Email address already exist'], status: 400);
        }

        $user = User::where('email', $email)
        ->where('phone_number', $phone_number)->first();
        if($user && (!isset($request->verification_code) || empty($request->verification_code))){
            
            $user->verification_code = $verification_code;
            $user->verification_code_expires_at = Carbon::now()->addMinutes(10);
            $user->save();
            $this->send_verification_code($request, false, $verification_code);
            return response()->json(['Message' => 'User created successfully'], 200);
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
            $names = explode(' ', $request->full_name, 2);
    
        
            $user = User::where('email', $email)
            ->where('phone_number', $phone_number)
            ->update([
                'phone_number' => $phone_number,
                'first_name' => $names[0],
                'last_name' => $names[1] ?? "",
                'password' => Hash::make($request->password),
                'user_category' => json_encode($category),
                'agent_id' => $agent_id ?? null,
                'uuid' => $uuid,
                'address' => $request->address ?? "",
                'nin' => $request->nin ?? "",
            ]);

            if($user) return response()->json(['message' => 'Account verified successfully'], 200);
            else return response()->json(['error' => 'Ooops! An error occured. Please try again'], 400);
        }
        
        $user = User::create([
            'email' => $email ?? "",
            'phone_number' => $phone_number,
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
        Request $request, bool $returnResponse = true, int $verification_code = null
    )
    {

        $email = $request->contact;

        
        if(!empty($email)){
            
            $user = User::where('email', $email)->first();
            if($user){

                if(empty($verification_code)){

                    $verification_code = str_pad(rand(0, 99999), 5, 0, STR_PAD_LEFT);
                    $user->verification_code = $verification_code;
                    $user->verification_code_expires_at = Carbon::now()->addMinutes(10);
                    $user->save();
                }

                Mail::to($email)->send(new ConfirmationEmail($request->full_name, $verification_code));
                
                if($returnResponse)
                return response()->json(['Message' => 'Verification code sent to your email address'], 200);
            }
            else return response()->json(['error' => 'User not found'], 400);
            

        }
        
    }

    public function verify_account(Request $request){
        
        $request->validate([
            'contact' => 'required',
            'verification_code' => 'required|numeric|digits:5'
        ]);

        $is_email = filter_var($request->contact, FILTER_VALIDATE_EMAIL);
        $email = $is_email == false ? "" : $is_email;
        $phone_number = $is_email == false ? $request->contact : "";

        $user = User::where([
            'email' => $email,
            'phone_number' => $phone_number,
            'verification_code' => $request->verification_code
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
        else return ['status' => false, 'error' => 'Invalid ID or verification code'];

    }
}
