<?php

use App\Models\User;
use App\Enum\General;
use App\Contracts\SMS;
use App\Models\Mailing;
use App\DTO\SendCodeData;
use App\Jobs\ProcessMail;
use App\Libraries\Utility;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Database\Eloquent\Model;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Support\Facades\Log;
use ImageKit\ImageKit;
use Tymon\JWTAuth\Facades\JWTAuth;

if (!function_exists('authUser')) {
    function authUser() {
        $user = request()->get('auth_user')
            ?? Auth::guard('api')->user()
            ?? JWTAuth::user();

        return (object) $user;
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

if (!function_exists('uploadToImageKit')) {
    function uploadToImageKit($file, $folder = 'uploads')
    {
        $imageKit = new ImageKit(
            config('services.imagekit.public_key'),
            config('services.imagekit.private_key'),
            config('services.imagekit.endpoint_key')
        );

        $uploadFile = fopen($file->getRealPath(), 'r');

        $uploadResponse = $imageKit->upload([
            "file" => $uploadFile,
            "fileName" => $file->getClientOriginalName(),
            "folder" => $folder
        ]);

        if (isset($uploadResponse->result->url)) {
            return [
                'url' => $uploadResponse->result->url,
                'public_id' => $uploadResponse->result->fileId,
            ];
        }

        return ['url' => null, 'public_id' => null];
    }
}

if (!function_exists('deleteOldFile')) {
    function deleteOldFile($publicId)
    {
        if (!$publicId) {
            return;
        }

        try {
            if (preg_match('/^[a-f0-9]{24}$/', $publicId)) {
                $imageKit = new \ImageKit\ImageKit(
                    config('services.imagekit.public_key'),
                    config('services.imagekit.private_key'),
                    config('services.imagekit.endpoint_key')
                );
                $imageKit->deleteFile($publicId);
            } else {
                Cloudinary::destroy($publicId);
            }
        } catch (\Throwable $e) {
            Log::error("Failed to delete file: {$e->getMessage()}");
        }
    }
}

if (!function_exists('uploadFile')) {
    function uploadFile($request, $key, $folder, $oldPublicId = null)
    {
        deleteOldFile($oldPublicId);

        if ($request->hasFile($key)) {
            $file = $request->file($key);
            try {
                $image = $file->storeOnCloudinary($folder);
                return [
                    'url' => $image->getSecurePath(),
                    'public_id' => $image->getPublicId(),
                ];
            } catch (\Throwable $e) {
                return uploadToImageKit($file, $folder);
            }
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
            deleteOldFile($oldPublicId);

            if ($request->hasFile($key)) {
                $file = $request->file($key);
                try {
                    $image = $file->storeOnCloudinary($folder);
                    $results[$key] = [
                        'url' => $image->getSecurePath(),
                        'public_id' => $image->getPublicId(),
                    ];
                } catch (\Throwable $e) {
                    $results[$key] = uploadToImageKit($file, $folder);
                }
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

            $originalName = $file->getClientOriginalName();
            $oldPublicId = $oldPublicIds[$originalName] ?? null;
            deleteOldFile($oldPublicId);

            try {
                $image = $file->storeOnCloudinary($folder);
                $results[] = [
                    'url' => $image->getSecurePath(),
                    'public_id' => $image->getPublicId(),
                ];
            } catch (\Throwable $e) {
                $results[] = uploadToImageKit($file, $folder);
            }
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
        $key ??= config('security.encoding_key');
        return Utility::encrypt($data, $key);
    }
}

if (! function_exists('decryptData')) {
    function decryptData($data, $key = null) {
        $key ??= config('security.encoding_key');
        return Utility::decrypt($data, $key);
    }
}

if (! function_exists('formatPhoneNumber')) {
    function formatPhoneNumber(string $phone_number): ?string
    {
        if (empty($phone_number)) {
            return null;
        }

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

if (! function_exists('sendCode')) {
    function sendCode($request, SendCodeData $payload) {
        $channels = [
            'email' => function () use ($payload) {
                mailSend(
                    $payload->type,
                    $payload->user,
                    $payload->subject,
                    $payload->mailable,
                    $payload->data
                );
            },
            'sms' => function () use ($payload) {
                app(abstract: SMS::class)->sendSms(
                    $payload->phone,
                    $payload->message
                );
            },
        ];

        $method = $request->method ?? null;

        if (isset($channels[$method])) {
            $channels[$method]();
        } else {
            throw new \InvalidArgumentException("Unsupported method: {$method}");
        }
    }
}

if (!function_exists('getUserTypes')){
    function getUserTypes(?Model $user = null){
        if(!$user){
            $user = Auth::user();
        }
        return explode(',', $user->user_category);
    }
}

if (!function_exists('hasSetSecurityAnswer')) {
    function hasSetSecurityAnswer(int $userId): bool
    {
        $user = User::find($userId);

        if (!$user) {
            return false;
        }

        if ($user->security_question_id && $user->security_answer) {
            return true;
        }

        return false;
    }
}

if (!function_exists(function: 'toObject')) {
    function toObject($array): object
    {
        if (!is_array($array)) {
            return (object) [];
        }

        $object = new \stdClass();
        foreach ($array as $key => $value) {
            $object->$key = is_array($value) ? toObject($value) : $value;
        }

        return $object;
    }

}

