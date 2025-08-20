<?php

namespace App\Services\Admin;

use App\Services\Curl\BulkCurlService;
use App\Services\Curl\PostCurlService;

class PayoutService
{
    public static function paystackTransfer($fields)
    {
        $url = "https://api.paystack.co/transfer";
        $token = config('paystack.secretKey');

        $headers = [
            'Accept' => 'application/json',
            'Authorization' => "Bearer {$token}",
        ];

        $data = (new PostCurlService($url, $headers, $fields))->execute();

        if($data['status'] === false) {
            return [
                'status' => false,
                'message' => null,
                'data' => null
            ];
        }

        return [
            'status' => true,
            'message' => null,
            'data' => $data
        ];
    }

    public static function paystackBulkTransfer(array $transfers)
    {
        $url = "https://api.paystack.co/transfer/bulk";
        $token = config('paystack.secretKey');

        $headers = [
            'Authorization' => "Bearer {$token}",
            'Accept' => 'application/json',
            'Cache-Control' => 'no-cache',
        ];

        $body = [
            'currency' => 'NGN',
            'source' => 'balance',
            'transfers' => array_map(function ($t) {
                return [
                    'reference' => $t['reference'],
                    'amount' => $t['amount'],
                    'recipient' => $t['recipient'],
                    'reason' => $t['reason'],
                ];
            }, $transfers),
        ];

        $response = (new BulkCurlService($url, $headers, $body))->execute();

        if (!isset($response['status']) || $response['status'] === false) {
            return [
                'status' => false,
                'message' => $response['message'],
                'data' => $response
            ];
        }

        return [
            'status' => true,
            'message' => $response['message'] ?? 'Bulk transfer queued',
            'data' => $response['data']
        ];
    }
}

