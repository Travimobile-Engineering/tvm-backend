<?php

use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

if (!function_exists('getRandomNumber')) {
    function getRandomNumber()
    {
        return 'TVM-' . Str::random(11);
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
        do{
            $str = Str::random($length);
        }
        while(DB::table($table)->where($column, $str)->exists());
        return $str;
    }
}


