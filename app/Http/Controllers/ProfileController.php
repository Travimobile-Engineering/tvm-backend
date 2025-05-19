<?php

namespace App\Http\Controllers;

use App\Enum\UserType;
use App\Trait\HttpResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\PassengerProfileResource;
use App\Services\ProfileService;
use App\Models\User;

class ProfileController extends Controller
{
    use HttpResponse;

    protected $user;

    public function __construct(
        protected ProfileService $profileService,
    )
    {
        $this->user = authUser();
    }

    public function index()
    {
        if ($this->user && $this->user?->user_category !== UserType::PASSENGER->value) {
            return $this->error(null, "You are not allowed to access this resource", 403);
        }

        $user = User::with(['userBank', 'securityQuestion'])
            ->where('id', $this->user->id)
            ->first();

        if (!$user) {
            return $this->error(null, "User not found", 404);
        }

        $data = new PassengerProfileResource($user);
        return $this->success($data, "Passenger profile");
    }

    public function edit(Request $request, $id)
    {
        return $this->profileService->edit($request, $id);
    }

    public function getDriverProfile()
    {
        return $this->profileService->getDriverProfile();
    }
}
