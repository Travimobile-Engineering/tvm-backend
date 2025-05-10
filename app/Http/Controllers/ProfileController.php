<?php

namespace App\Http\Controllers;

use App\Enum\UserType;
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
        if ($this->user && $this->user?->user_category !== UserType::PASSENGER->value) {
            return $this->error(null, "You are not allowed to access this resource", 403);
        }

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
