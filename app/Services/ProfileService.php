<?php

namespace App\Services;

use App\Enum\UserType;
use App\Http\Resources\DriverProfileResource;
use App\Http\Resources\FooProfileResource;
use App\Models\User;
use App\Trait\HttpResponse;
use Illuminate\Http\Request;

class ProfileService
{
    use HttpResponse;

    public function edit(Request $request, $id)
    {
        $user = User::find($id);

        if (! $user) {
            return $this->error('User not found', 404);
        }

        if ($request->has('full_name')) {
            $names = explode(' ', $request->full_name, 2);
            $first_name = $names[0];
            $last_name = $names[1] ?? '';
        }

        if ($request->has('profile_photo')) {
            $uploadResult = uploadFile($request, 'profile_photo', 'profile_photos');
        }

        if ($request->has('password')) {
            $request->validate([
                'password' => 'required|string|min:8|confirmed',
            ]);
            $password = bcrypt($request->password);
        }

        $user->update([
            'first_name' => $first_name ?? $user->first_name,
            'last_name' => $last_name ?? $user->last_name,
            'phone_number' => $request->phone_number ?? $user->phone_number,
            'email' => $request->email ?? $user->email,
            'gender' => $request->gender ?? $user->gender,
            'nin' => encryptData($request->nin) ?? $user->nin,
            'next_of_kin_full_name' => $request->next_of_kin_full_name ?? $user->next_of_kin_full_name,
            'next_of_kin_gender' => $request->next_of_kin_gender ?? $user->next_of_kin_gender,
            'next_of_kin_phone_number' => $request->next_of_kin_phone_number ?? $user->next_of_kin_phone_number,
            'profile_photo' => $uploadResult['url'] ?? $user->profile_photo,
            'public_id' => $uploadResult['public_id'] ?? $user->public_id,
            'password' => $password ?? $user->password,
        ]);

        return $this->success($user, 'User updated successfully');
    }

    public function getDriverProfile()
    {
        $auth = authUser();

        $user = User::with([
            'transitCompany',
            'vehicle',
            'vehicle.vehicleImages',
            'vehicle.tripSchedule',
            'documents',
            'driverTripPayments',
            'trips',
            'premiumUpgrades.vehicle',
            'unavailableDates',
            'userBank',
            'securityQuestion',
        ])
            ->findOrFail($auth->id);

        if ($user->user_category !== UserType::DRIVER->value) {
            return $this->error(null, 'You are not allowed to access this resource', 403);
        }

        $data = new DriverProfileResource($user);

        return $this->success($data, 'Driver profile retrieved successfully');
    }

    public function getFoo()
    {
        $auth = authUser();

        $user = User::with([
            'transitCompany',
            'vehicle',
            'vehicle.vehicleImages',
            'vehicle.tripSchedule',
            'documents',
            'driverTripPayments',
            'trips',
            'premiumUpgrades.vehicle',
            'unavailableDates',
            'userBank',
            'securityQuestion',
        ])
            ->findOrFail($auth->id);

        if ($user->user_category !== UserType::FOO->value) {
            return $this->error(null, 'You are not allowed to access this resource', 403);
        }

        $data = new FooProfileResource($user);

        return $this->success($data, 'Foo profile retrieved successfully');
    }
}
