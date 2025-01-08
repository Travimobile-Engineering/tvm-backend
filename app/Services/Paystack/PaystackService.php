<?php

namespace App\Services\Paystack;

use App\Services\Curl\PostCurlService;
use App\Trait\HttpResponse;

class PaystackService
{
    use HttpResponse;

    public static function createRecipient($user, $fields)
    {
        $url = "https://api.paystack.co/transferrecipient";
        $token = config('app.paystack_secret_key');

        $headers = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $token,
        ];

        $data = (new PostCurlService($url, $headers, $fields))->execute();

        self::logTransfer($user, $data);
    }

    public static function transfer($user, $fields)
    {
        $url = "https://api.paystack.co/transfer";
        $token = config('app.paystack_secret_key');

        $headers = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $token,
        ];

        $data = (new PostCurlService($url, $headers, $fields))->execute();

        if($data['status'] === false) {
            return response()->json([
                'status' => $data['status'],
                'message' => $data['message'],
                'data' => null
            ], 400);
        }

        self::logWithdraw($user, $data);

        return response()->json([
            'status' => $data['status'],
            'message' => $data['message'],
            'data' => null
        ], 200);
    }

    private static function logTransfer($user, $data)
    {
        $user->userTransferReceipient()->create([
            'name' => $data['name'],
            'recipient_code' => $data['recipient_code'],
            'data' => $data,
        ]);
    }

    private static function logWithdraw($user, $data)
    {
        $user->userWithdrawLogs()->create([
            'amount' => $data['amount'],
            'transfer_code' => $data['transfer_code'],
            'status' => $data['status'],
            'data' => $data,
            'ip_address' => request()->ip(),
            'device' => request()->header('User-Agent'),
        ]);
    }
}

