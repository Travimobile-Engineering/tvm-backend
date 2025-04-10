<?php

use App\Enum\General;
use App\Jobs\ProcessMail;
use App\Libraries\Utility;
use App\Models\User;
use App\Models\Mailing;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Support\Facades\Auth;

if (!function_exists('authUser')) {
    function authUser() {
        return Auth::guard('api')->user();
    }
}

if (!function_exists('getRandomString')) {
    function getRandomString()
    {
        return Str::random(10);
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

if (!function_exists('uploadFilesBatches')) {
    function uploadFilesBatches($request, $files, $folder, $oldPublicIds = [])
    {
        $results = [];
        foreach ($files as $key) {
            $oldPublicId = $oldPublicIds[$key] ?? null;

            if ($oldPublicId) {
                Cloudinary::destroy($oldPublicId);
            }

            if ($request->hasFile($key)) {
                $image = $request->file($key)->storeOnCloudinary($folder);
                $results[$key] = [
                    'url' => $image->getSecurePath(),
                    'public_id' => $image->getPublicId(),
                ];
            } else {
                $results[$key] = ['url' => null, 'public_id' => null];
            }
        }

        return $results;
    }
}

if (!function_exists('uploadFilesBatch')) {
    function uploadFilesBatch($files, $folder, $oldPublicIds = [])
    {
        $results = [];

        foreach ($files as $file) {
            if (!($file instanceof \Illuminate\Http\UploadedFile)) {
                continue;
            }

            $oldPublicId = $oldPublicIds[$file->getClientOriginalName()] ?? null;

            if ($oldPublicId) {
                Cloudinary::destroy($oldPublicId);
            }

            $image = $file->storeOnCloudinary($folder);

            $results[] = [
                'url' => $image->getSecurePath(),
                'public_id' => $image->getPublicId(),
            ];
        }

        return $results;
    }
}

if(!function_exists('getRouteStateAndTownNameFromTownId')){
    function getRouteStateAndTownNameFromTownId(int $id) :string{
        $town = DB::table('route_subregions')->where('id', $id)->first();
        $state = DB::table('states')->where('id', $town->state_id)->first();

        return $state->name." > ".$town->name;
    }
}

if (!function_exists('generateUniqueRandomString')) {
    function generateUniqueRandomString($table, $column, $length = 16) {
        do {
            $str = Str::random($length);
        } while (DB::table($table)->where($column, $str)->exists());

        return $str;
    }
}

if (!function_exists('generateUniqueNumber')) {
    function generateUniqueNumber($table, $column, $length = 10) {
        do {
            $number = str_pad(mt_rand(0, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
        } while (DB::table($table)->where($column, $number)->exists());

        return $number;
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
        $user = User::with(['userBank', 'userPin'])->find($userId);

        if (!$user) {
            return false;
        }

        $hasBankDetails = $user->userBank()->exists();

        $hasPin = $user->userPin()
            ->where('status', General::ACTIVE)
            ->exists();

        return $hasBankDetails && $hasPin;
    }
}

if (!function_exists('hasSetupPin')) {
    function hasSetupPin(int $userId): bool
    {
        $user = User::with(['userPin'])->find($userId);

        if (!$user) {
            return false;
        }

        return $user->userPin()
            ->where('status', General::ACTIVE)
            ->exists();
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

if (! function_exists('mailSend')) {
    function mailSend($type, $recipient, $subject, $mail_class, $payloadData = []) {
        $data = [
            'type' => $type,
            'email' => $recipient->email,
            'subject' => $subject,
            'body' => "",
            'mailable' => $mail_class,
            'scheduled_at' => now(),
            'payload' => array_merge($payloadData)
        ];

        $mailing = Mailing::saveData($data);
        dispatch(new ProcessMail($mailing->id));
    }
}

if (! function_exists('encryptData')) {
    function encryptData($data, $key = null) {
        $key = $key ?? config('security.encoding_key');
        return Utility::encrypt($data, $key);
    }
}

if (! function_exists('decryptData')) {
    function decryptData($data, $key = null) {
        $key = $key ?? config('security.encoding_key');
        return Utility::decrypt($data, $key);
    }
}

if (! function_exists('formatPhoneNumber')) {
    function formatPhoneNumber(string $phone_number): string
    {
        $phone_number = preg_replace('/\D/', '', $phone_number);

        if (preg_match('/^234[789][01]\d{8}$/', $phone_number)) {
            return $phone_number;
        }

        if (preg_match('/^0[789][01]\d{8}$/', $phone_number)) {
            return '234' . substr($phone_number, 1);
        }

        if (preg_match('/^\+234[789][01]\d{8}$/', $phone_number)) {
            return substr($phone_number, 1); // remove the '+' sign
        }

        return $phone_number;
    }
}


