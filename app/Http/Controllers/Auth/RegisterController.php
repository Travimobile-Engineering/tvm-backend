<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\AccountSignUpRequest;
use App\Http\Requests\CreateDriverRequest;
use App\Services\Auth\AuthService;
use App\Trait\HttpResponse;
use Illuminate\Http\Request;

class RegisterController extends Controller
{
    use HttpResponse;

    public function __construct(
        protected AuthService $service
    ) {}

    public function accountSignUp(AccountSignUpRequest $request)
    {
        return $this->service->accountSignUp($request);
    }

    public function verifyAcount(Request $request)
    {
        return $this->service->verifyAcount($request);
    }

    public function resendCode(Request $request)
    {
        return $this->service->resendCode($request);
    }

    public function createDriver(CreateDriverRequest $request)
    {
        return $this->service->createDriver($request);
    }

    public function verifyDriverAccount(Request $request)
    {
        return $this->service->verifyDriverAccount($request);
    }
}
