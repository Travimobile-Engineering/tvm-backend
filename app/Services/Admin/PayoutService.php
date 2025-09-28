<?php

namespace App\Services\Admin;

use App\Services\Client\HttpService;
use App\Services\Client\RequestOptions;
use App\Services\Curl\PostCurlService;

class PayoutService
{
    public static function paystackTransfer($fields)
    {
        $url = 'https://api.paystack.co/transfer';
        $token = config('paystack.secretKey');

        $headers = [
            'Accept' => 'application/json',
            'Authorization' => "Bearer {$token}",
        ];

        $data = (new PostCurlService($url, $headers, $fields))->execute();

        if ($data['status'] === false) {
            return [
                'status' => false,
                'message' => null,
                'data' => null,
            ];
        }

        return [
            'status' => true,
            'message' => null,
            'data' => $data,
        ];
    }

    public static function paystackBulkTransfer(array $transfers)
    {
        $url = config('services.payment.url').'/paystack/bulk-transfer';
        $service = app(HttpService::class);

        try {
            $body = [
                'currency' => 'NGN',
                'source' => 'balance',
                'transfers' => array_map(function ($t) {
                    return [
                        'amount' => (int) $t['amount'],
                        'reference' => $t['reference'],
                        'reason' => $t['reason'],
                        'recipient' => $t['recipient'],
                    ];
                }, $transfers),
            ];

            $response = $service->post(
                $url,
                new RequestOptions(
                    data: $body
                )
            );

            if (! in_array($response->status(), [201, 200])) {
                return [
                    'status' => false,
                    'message' => 'Failed',
                    'data' => null,
                ];
            }

            $data = $response->json();

            return [
                'status' => true,
                'message' => $data['message'] ?? 'Bulk transfer queued',
                'data' => $data['data'],
            ];
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
