<?php

namespace App\Http\Controllers\Auth;
use App\Trait\HttpResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\Auth\AuthService;


class RegisterController extends Controller
{
    use HttpResponse;
    public function __construct(protected AuthService $service){
        //
    }
    //method to register a new user


    public function accountSignUp(Request $request)
    {
        $request->validate([
            'full_name' => ['required', 'string', 'max:200'],
            'email' => ['required', 'string'],
            'phone_number' => ['required_if:email,null'],
            'user_category' => ['required', 'string', 'in:passenger,driver,agent'],
            'password' => ['required', 'string', 'confirmed', 'min:8']
        ]);

        return $this->service->accountSignUp($request);
    }

    public function verifyAcount(Request $request)
    {
        return $this->service->verifyAcount($request);
    }

    public function resendCode(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        return $this->service->resendCode($request);
    }

}

