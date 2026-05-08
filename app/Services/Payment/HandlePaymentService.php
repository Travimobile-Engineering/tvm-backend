<?php

namespace App\Services\Payment;

use App\Contracts\Payment;

class HandlePaymentService
{
    public function __construct(protected Payment $paymentProcessor) {}

    public function process(array $paymentDetails)
    {
        return $this->paymentProcessor->processPayment($paymentDetails);
    }
}
