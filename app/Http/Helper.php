<?php

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Support\Facades\Mail;

if (!function_exists('authUser')) {
    function authUser() {
        return auth()->user();
    }
}

if (!function_exists('getRandomNumber')) {
    function getRandomNumber()
    {
        return 'TVM-' . Str::random(11);
    }
}

if (!function_exists('getCode')) {
    function getCode()
    {
        return str_pad(rand(0, 99999), 5, 0, STR_PAD_LEFT);
    }
}

if (!function_exists('uploadFile')) {
    function uploadFile($request, $key, $folder, $oldPublicId = null)
    {
        if ($oldPublicId) {
            Cloudinary::destroy($oldPublicId);
        }

        if ($request->hasFile($key)) {
            $image = $request->file($key)->storeOnCloudinary($folder);
            return [
                'url' => $image->getSecurePath(),
                'public_id' => $image->getPublicId(),
            ];
        }
        return ['url' => null, 'public_id' => null];
    }
}

if(!function_exists('getRouteStateAndTownNameFromTownId')){
    function getRouteStateAndTownNameFromTownId(int $id) :string{
        $town = DB::table('route_subregions')->where('id', $id)->first();
        $state = DB::table('states')->where('id', $town->state_id)->first();

        return $state->name." > ".$town->name;
    }
}

if(!function_exists('generateUniqueRandomString')){
    function generateUniqueRandomString($table, $column, $length = 16){
        do $str = Str::random($length);
        while(DB::table($table)->where($column, $str)->exists());
        return $str;
    }
}

if(!function_exists('generateVerificationCode')){
    function generateVerificationCode($length = 5){
        return str_pad(rand(10000, 99999), $length, 0);
    }
}

if (!function_exists('sendMail')) {
    function sendMail($to, $mail)
    {
        Mail::to($to)->send($mail);
    }
}

if (!function_exists('hasSetupWallet')) {
    function hasSetupWallet(int $userId): bool
    {
        $user = User::with(['driverBank', 'driverPin'])->find($userId);

        if (!$user) {
            return false;
        }

        $hasBankDetails = $user->driverBank()->exists();

        $hasPin = $user->driverPin()
            ->where('status', 'active')
            ->exists();

        return $hasBankDetails && $hasPin;
    }
}

if (!function_exists('hasOnboarded')) {
    function hasOnboarded(int $userId): bool
    {
        $user = User::with(['vehicle', 'documents'])->find($userId);

        if (!$user) {
            return false;
        }

        $hasVehicleDetails = $user->vehicle()->exists();
        $hasDocumentsDetails = $user->documents()->exists();

        return $user && $hasVehicleDetails && $hasDocumentsDetails;
    }
}

