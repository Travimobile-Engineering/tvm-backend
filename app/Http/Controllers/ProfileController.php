<?php

namespace App\Http\Controllers;

use App\Trait\HttpResponse;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Http\Controllers\Controller;
use App\Http\Resources\PassengerProfileResource;
use App\Services\ProfileService;

class ProfileController extends Controller
{
    use HttpResponse;

    protected $user;

    public function __construct(
        protected ProfileService $profileService,
    )
    {
        $this->user = JWTAuth::user();
    }

    //method to get the authenticated user
    public function index()
    {
        $user = new PassengerProfileResource($this->user);
        return $this->success($user, "Passenger profile");
    }

    //Method to update user data
    public function edit(Request $request, $id)
    {
        return $this->profileService->edit($request, $id);
    }

    public function getDriverProfile()
    {
        return $this->profileService->getDriverProfile();
    }
}
