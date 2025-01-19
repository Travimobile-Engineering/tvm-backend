<?php

namespace App\Services;

use App\Enum\PaymentMethod;
use App\Trait\HttpResponse;
use App\Trait\TripBookingTrait;
use Illuminate\Support\Facades\Auth;
use App\Services\Payment\PaystackPaymentProcessor;

class TripBookService
{
    use HttpResponse, TripBookingTrait;

    public function store($request)
    {
        $user = Auth::user();
        $amount_paid = $request->amount_paid;
        $trip = null;
        $result = null;
        $paymentProcessor = null;

        if ($request->payment_method == PaymentMethod::PAYSTACK) {
            $trip = $this->tripCheck($request);

            if ($trip instanceof \Illuminate\Http\JsonResponse && $trip->getStatusCode() !== 200) {
                return $trip;
            }
        }

        match($request->payment_method) {
            PaymentMethod::WALLET => $result = $this->walletPayment($amount_paid, $request, $user),
            PaymentMethod::PAYSTACK => $paymentProcessor = new PaystackPaymentProcessor(),
            default => throw new \Exception('Invalid payment method'),
        };

        return $this->processPayment($request, $result, $paymentProcessor, $trip);
    }
}




