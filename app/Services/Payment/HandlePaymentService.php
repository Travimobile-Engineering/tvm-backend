<?php

namespace App\Services\Payment;

use App\Contracts\Payment;

class HandlePaymentService
{
    protected $paymentProcessor;

    public function __construct(Payment $paymentProcessor)
    {
        $this->paymentProcessor = $paymentProcessor;
    }

    public function process(array $paymentDetails)
    {
        return $this->paymentProcessor->processPayment($paymentDetails);
    }
}


