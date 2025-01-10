<?php

namespace App\Http\Controllers;

use App\Services\PaymentService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(private PaymentService $service)
    {}

    public function webhook(Request $request)
    {
        return $this->service->webhook($request);
    }
}
