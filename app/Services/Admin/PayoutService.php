<?php

namespace App\Services\Admin;

use App\Services\Client\HttpService;
use App\Services\Client\RequestOptions;

class PayoutService
{
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

            if ($response === null && ! isset($response['data'])) {
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
