<?php

namespace App\Services;

use App\Enum\PaymentMethod;
use App\Models\PaymentLog;
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
        $result = null;
        $paymentProcessor = null;

        match($request->payment_method) {
            PaymentMethod::WALLET => $result = $this->walletPayment($amount_paid, $request, $user),
            PaymentMethod::PAYSTACK => $paymentProcessor = new PaystackPaymentProcessor(),
            default => throw new \Exception('Invalid payment method'),
        };

        return $this->processPayment($request, $result, $paymentProcessor);
    }

    public function getPaymentRef($reference)
    {
        $paymentLog = PaymentLog::with('tripBooking')
            ->where('reference', $reference)
            ->first();

        if(! $paymentLog) {
            return $this->error(null, 'Invalid payment reference', 400);
        }

        $data = (object) [
            'booking_id' => $paymentLog->tripBooking?->booking_id,
            'status' => $paymentLog->status,
        ];

        return $this->success($data, 'Payment reference fetched successfully');
    }
}





