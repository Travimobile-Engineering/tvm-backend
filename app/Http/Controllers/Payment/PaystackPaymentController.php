<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Http\Requests\InitializePaystackTransactionRequest;

class PaystackPaymentController extends Controller
{
    public function intializeTransaction(InitializePaystackTransactionRequest $request)
    {
        // return $this->service->intializeTransaction($request);
        return null;
    }
}
