<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Trait\HttpResponse;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use App\Http\Resources\DriverProfileResource;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    use HttpResponse;

    protected $user;

    public function __construct(){
        $this->user = JWTAuth::user();
    }

    //method to get the authenticated user
    public function index(){

        return response()->json($this->user);
    }

    //Method to update user data
    public function edit(Request $request){

        $id = $request->route('id');

        if($this->user->id == $id){

            $updates = collect($request->all());

            if($updates->has('full_name')){
                $names = explode(' ', $updates['full_name'], 2);
                $updates['first_name'] = $names[0];
                $updates['last_name'] = $names[1] ?? '';
            }

            $updates = $updates->filter(function($value, $key){
                return !empty($value) && $key != 'email' && Schema::hasColumn('users', $key);
            });

            if($updates->has('password')){

                try{
                    $request->validate([
                        'password' => 'min:8',
                    ]);
                }catch(ValidationException $e){
                    return response()->json(['error' => $e->errors()]);
                }

                $updates['password'] = Hash::make($updates['password']);
            }

            $uploadResult = uploadFile($request, 'profile_photo', 'profile_photos');
            // dd($request);
            $updates['profile_photo_url'] = $uploadResult['url'];


            $user = User::where('id', $id)
                ->update($updates->toArray());

            return response()->json([
                'message' => 'User data updated successfully',
                'user' => User::find($id),
            ]);
        }

        else return response()->json(['error' => 'Invalid user id']);
    }

    public function getDriverProfile()
    {
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
            ])
            ->findOrFail($this->user->id);

        $data = new DriverProfileResource($user);

        return $this->success($data, 'Driver profile retrieved successfully');
    }
}
