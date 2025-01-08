<?php

use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

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

if (!function_exists('sendMail')) {
    function sendMail($to, $mail)
    {
        Mail::to($to)->send($mail);
    }
}



