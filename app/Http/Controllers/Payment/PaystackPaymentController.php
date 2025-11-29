<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Http\Requests\InitializePaystackTransactionRequest;
use App\Services\PaystackPaymentService;

class PaystackPaymentController extends Controller
{
    protected $service;

    public function __construct()
    {
        $this->service = new PaystackPaymentService;
    }

    public function intializeTransaction(InitializePaystackTransactionRequest $request)
    {
        return $this->service->intializeTransaction($request);

    }

    public function verifyTransaction($transactionReference, $amount)
    {
        return $this->service->verifyTransaction($transactionReference, $amount);
    }
}
