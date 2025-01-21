<?php

namespace App\Services\Payment;

use App\Contracts\Payment;
use App\Trait\HttpResponse;
use Unicodeveloper\Paystack\Facades\Paystack;

class PaystackPaymentProcessor implements Payment
{
    use HttpResponse;

    public function processPayment(array $paymentDetails)
    {
        try {
            $paystackInstance = Paystack::getAuthorizationUrl($paymentDetails);
            return [
                'status' => 'success',
                'data' => $paystackInstance,
            ];
        } catch (\Exception $e) {
            return $this->error(null, $e->getMessage(), 500);
        }
    }
}



