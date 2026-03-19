<?php

use App\Contracts\SMS;
use App\DTO\SendCodeData;
use App\Enum\CommissionEnum;
use App\Enum\General;
use App\Enum\UserType;
use App\Jobs\ProcessMail;
use App\Libraries\Utility;
use App\Models\Fee;
use App\Models\FeesShareFormula;
use App\Models\Mailing;
use App\Models\User;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use ImageKit\ImageKit;

if (! function_exists('authUser')) {
    function authUser()
    {
        return Auth::guard('api')->user();
    }
}

if (! function_exists('getRandomString')) {
    function getRandomString()
    {
        return Str::random(10);
    }
}

if (! function_exists('getRandomNumber')) {
    function getRandomNumber()
    {
        return 'TVM-'.Str::random(11);
    }
}

if (! function_exists('getCode')) {
    function getCode()
    {
        return str_pad(rand(0, 99999), 5, 0, STR_PAD_LEFT);
    }
}

if (! function_exists('uploadToImageKit')) {
    function uploadToImageKit($file, $folder = 'uploads')
    {
        $imageKit = new ImageKit(
            config('services.imagekit.public_key'),
            config('services.imagekit.private_key'),
            config('services.imagekit.endpoint_key')
        );

        $uploadFile = fopen($file->getRealPath(), 'r');

        $uploadResponse = $imageKit->upload([
            'file' => $uploadFile,
            'fileName' => $file->getClientOriginalName(),
            'folder' => $folder,
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

if (! function_exists('deleteOldFile')) {
    function deleteOldFile($publicId)
    {
        if (! $publicId) {
            return;
        }

        try {
            if (preg_match('/^[a-f0-9]{24}$/', $publicId)) {
                $imageKit = new ImageKit(
                    config('services.imagekit.public_key'),
                    config('services.imagekit.private_key'),
                    config('services.imagekit.endpoint_key')
                );
                $imageKit->deleteFile($publicId);
            } else {
                Cloudinary::destroy($publicId);
            }
        } catch (Throwable $e) {
            Log::error("Failed to delete file: {$e->getMessage()}");
        }
    }
}

if (! function_exists('uploadFile')) {
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
            } catch (Throwable $e) {
                return uploadToImageKit($file, $folder);
            }
        }

        return ['url' => null, 'public_id' => null];
    }
}

if (! function_exists('uploadFilesBatches')) {
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
                } catch (Throwable $e) {
                    $results[$key] = uploadToImageKit($file, $folder);
                }
            } else {
                $results[$key] = ['url' => null, 'public_id' => null];
            }
        }

        return $results;
    }
}

if (! function_exists('uploadFilesBatch')) {
    function uploadFilesBatch($files, $folder, $oldPublicIds = [])
    {
        $results = [];

        foreach ($files as $file) {
            if (! ($file instanceof UploadedFile)) {
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
            } catch (Throwable $e) {
                $results[] = uploadToImageKit($file, $folder);
            }
        }

        return $results;
    }
}

if (! function_exists('getRouteStateAndTownNameFromTownId')) {
    function getRouteStateAndTownNameFromTownId(int $id): string
    {
        $town = DB::table('route_subregions')->where('id', $id)->first();
        $state = DB::table('states')->where('id', $town->state_id)->first();

        return $state->name.' > '.$town->name;
    }
}

if (! function_exists('generateUniqueRandomString')) {
    function generateUniqueRandomString($table, $column, $length = 16)
    {
        do {
            $str = Str::random($length);
        } while (DB::table($table)->where($column, $str)->exists());

        return $str;
    }
}

if (! function_exists('generateUniqueNumber')) {
    function generateUniqueNumber($table, $column, $length = 10)
    {
        do {
            $number = str_pad(mt_rand(0, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
        } while (DB::table($table)->where($column, $number)->exists());

        return $number;
    }
}

if (! function_exists('generateVerificationCode')) {
    function generateVerificationCode($length = 5)
    {
        return str_pad(rand(10000, 99999), $length, 0);
    }
}

if (! function_exists('sendMail')) {
    function sendMail($to, $mail)
    {
        Mail::to($to)->send($mail);
    }
}

if (! function_exists('sendSmS')) {
    function sendSmS($phone, $message)
    {
        return app(SMS::class)->sendSms($phone, $message);
    }
}

if (! function_exists('hasSetupWallet')) {
    function hasSetupWallet(int $userId): bool
    {
        $user = User::with(['userBank', 'userPin'])->find($userId);

        if (! $user) {
            return false;
        }

        $hasBankDetails = $user->userBank()->exists();
        $hasPin = hasSetupPin($userId);

        return $hasBankDetails && $hasPin;
    }
}

if (! function_exists('hasSetupPin')) {
    function hasSetupPin(int $userId): bool
    {
        $user = User::with(['userPin'])->find($userId);

        if (! $user) {
            return false;
        }

        return $user->userPin()
            ->where('status', General::ACTIVE)
            ->exists();
    }
}

if (! function_exists('hasOnboarded')) {
    function hasOnboarded(int $userId): bool
    {
        $user = User::with(['vehicle', 'documents'])->find($userId);

        if (! $user) {
            return false;
        }

        $hasVehicleDetails = $user->vehicle()->exists();
        $hasDocumentsDetails = $user->documents()->exists();

        return $user && $hasVehicleDetails && $hasDocumentsDetails;
    }
}

if (! function_exists('mailSend')) {
    function mailSend($type, $recipient, $subject, $mail_class, $payloadData = [])
    {
        $data = [
            'type' => $type,
            'email' => $recipient->email,
            'subject' => $subject,
            'body' => '',
            'mailable' => $mail_class,
            'scheduled_at' => now(),
            'payload' => array_merge($payloadData),
        ];

        $mailing = Mailing::saveData($data);
        dispatch(new ProcessMail($mailing->id));
    }
}

if (! function_exists('encryptData')) {
    function encryptData($data, $key = null)
    {
        $key ??= config('security.encoding_key');

        return Utility::encrypt($data, $key);
    }
}

if (! function_exists('decryptData')) {
    function decryptData($data, $key = null)
    {
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
            return '234'.substr($phone_number, 1);
        }

        if (preg_match('/^\+234[789][01]\d{8}$/', $phone_number)) {
            return substr($phone_number, 1); // remove the '+' sign
        }

        return $phone_number;
    }
}

if (! function_exists('sendCode')) {
    function sendCode($request, SendCodeData $payload)
    {
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
                    formatPhoneNumber($payload->phone),
                    $payload->message
                );
            },
        ];

        $method = $request->method ?? null;

        if (isset($channels[$method])) {
            $channels[$method]();
        } else {
            throw new InvalidArgumentException("Unsupported method: {$method}");
        }
    }
}

if (! function_exists('getUserTypes')) {
    function getUserTypes(?Model $user = null)
    {
        if (! $user) {
            $user = Auth::user();
        }

        return explode(',', $user->user_category);
    }
}

if (! function_exists('hasSetSecurityAnswer')) {
    function hasSetSecurityAnswer(int $userId): bool
    {
        $user = User::find($userId);

        if (! $user) {
            return false;
        }

        if ($user->security_question_id && $user->security_answer) {
            return true;
        }

        return false;
    }
}

if (! function_exists('getFee')) {
    function getFee(string $name): int|float
    {
        static $localCache = [];

        $name = strtolower($name);

        if (array_key_exists($name, $localCache)) {
            return $localCache[$name];
        }

        $amount = Cache::remember("fee:amount:$name", now()->addHour(), function () use ($name) {
            return Fee::whereRaw('LOWER(name) = ?', [$name])->value('amount');
        });

        if ($amount === null) {
            $amount = 50;
        }

        $localCache[$name] = $amount;

        return $amount;
    }
}

if (! function_exists('driversCreatedByAgent')) {
    function driversCreatedByAgent(int $agentId)
    {
        $agent = User::find($agentId);

        if (! $agent) {
            return collect();
        }

        return $agent->createdDrivers()
            ->where('user_category', UserType::DRIVER->value)
            ->get();
    }
}

if (! function_exists('usersCreated')) {
    function usersCreated(int $userId)
    {
        $user = User::find($userId);

        if (! $user) {
            return collect();
        }

        return $user->createdUsers()
            ->get();
    }
}

if (! function_exists('generateUniqueString')) {
    function generateUniqueString($table, $column, $length = 10)
    {
        $attempts = 0;
        $maxAttempts = 10;

        do {
            if ($attempts++ > $maxAttempts) {
                throw new Exception("Unable to generate unique string after {$maxAttempts} attempts.");
            }

            $string = Str::random($length);

        } while (DB::table($table)->where($column, $string)->exists());

        return $string;
    }
}

if (! function_exists('generateReference')) {
    /**
     * Generate a unique reference.
     *
     * @param  string  $prefix  Optional prefix like 'TRF' or 'INV'
     * @param  string|null  $table  Optional table to check uniqueness
     * @param  string  $column  Column in the table to check (default: 'txn_reference')
     */
    function generateReference(string $prefix = '', ?string $table = null, string $column = 'txn_reference'): string
    {
        do {
            $random = strtoupper(Str::random(10));
            $timestamp = now()->format('YmdHis');
            $reference = $prefix.$timestamp.$random;

            $exists = false;

            if ($table) {
                $exists = DB::table($table)->where($column, $reference)->exists();
            }

        } while ($exists);

        return $reference;
    }
}

if (! function_exists('getCharge')) {
    /**
     * Get charges for the given types.
     */
    function getCharge(array $types, float $default = 0.00): array
    {
        // Fetch charges in one query
        $charges = Fee::whereIn('name', $types)
            ->pluck('amount', 'name')
            ->toArray();

        // Ensure all requested types exist, fall back to default
        return collect($types)
            ->mapWithKeys(fn ($type) => [$type => (float) ($charges[$type] ?? $default)])
            ->toArray();
    }
}

if (! function_exists('commissionValue')) {
    /**
     * Get commission value from database with fallback to enum defaults
     */
    function commissionValue(string $commissionType, CommissionEnum $commissionRole): float
    {
        try {
            $percentage = FeesShareFormula::where('type', $commissionType)
                ->where('slug', $commissionRole->slug())
                ->value('percentage');

            return $percentage ?? $commissionRole->value;
        } catch (Exception $e) {
            return $commissionRole->value;
        }
    }
}
