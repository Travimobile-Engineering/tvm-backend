<?php

namespace App\Contracts;

interface Payment
{
    public function processPayment(array $paymentDetails);
}
