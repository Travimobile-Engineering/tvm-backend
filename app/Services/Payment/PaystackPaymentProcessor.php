<?php

namespace App\Services\Payment;

use App\Contracts\Payment;
use App\Services\Client\HttpService;
use App\Services\Client\RequestOptions;
use App\Trait\HttpResponse;

class PaystackPaymentProcessor implements Payment
{
    use HttpResponse;

    public function __construct(protected HttpService $httpService) {}

    public function processPayment(array $paymentDetails)
    {
        $url = config('services.payment.url').'/paystack/initialize';

        try {
            $response = $this->httpService->post(
                $url,
                new RequestOptions(
                    data: $paymentDetails
                )
            );

            if ($response->failed()) {
                return [
                    'status' => false,
                    'message' => 'Failed',
                    'data' => null,
                ];
            }

            $data = $response->json();

            return [
                'status' => 'success',
                'message' => $data['message'],
                'data' => $data['data'],
            ];
        } catch (\Exception $e) {
            return $this->error(null, $e->getMessage(), 500);
        }
    }
}
