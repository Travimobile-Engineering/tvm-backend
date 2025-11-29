<?php

namespace App\Services;

use App\Enum\PaymentMethod;
use App\Models\PaymentLog;
use App\Services\Payment\PaystackPaymentProcessor;
use App\Trait\HttpResponse;
use App\Trait\TripBookingTrait;
use Illuminate\Support\Facades\Auth;

class TripBookService
{
    use HttpResponse, TripBookingTrait;

    public function store($request)
    {
        $user = Auth::user();
        $suspend = true;

        if (! $user) {
            return $this->error(null, 'User not found', 404);
        }

        // if (app()->environment('production') && $suspend) {
        //     return $this->error(null, "Booking has been temporarily suspended", 400);
        // }

        $amount_paid = $request->amount_paid;
        $result = null;
        $paymentProcessor = null;

        match ($request->payment_method) {
            PaymentMethod::WALLET => $result = $this->walletPayment($amount_paid, $request, $user),
            PaymentMethod::PAYSTACK => $paymentProcessor = new PaystackPaymentProcessor,
            default => throw new \Exception('Invalid payment method'),
        };

        return $this->processPayment($request, $result, $paymentProcessor, $user);
    }

    public function getPaymentRef($reference)
    {
        $paymentLog = PaymentLog::with('tripBooking')
            ->where('reference', $reference)
            ->first();

        if (! $paymentLog) {
            return $this->error(null, 'Invalid payment reference', 400);
        }

        $data = (object) [
            'booking_id' => $paymentLog->tripBooking?->booking_id,
            'status' => $paymentLog->status,
        ];

        return $this->success($data, 'Payment reference fetched successfully');
    }
}
