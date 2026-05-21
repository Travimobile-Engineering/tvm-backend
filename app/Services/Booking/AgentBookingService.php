<?php

namespace App\Services\Booking;

use App\Enum\PaymentMethod;
use App\Http\Requests\AgentBookingRequest;
use App\Models\User;
use App\Trait\HttpResponse;
use App\Trait\PaymentLogTrait;
use App\Trait\TripBookingTrait;

class AgentBookingService
{
    use HttpResponse, PaymentLogTrait, TripBookingTrait;

    // Agent booking
    public function execute(
        User $user,
        AgentBookingRequest $request,
        float $amountPaid,
    ) {
        $result = null;
        $paymentProcessor = null;

        $charges = [
            'Admin Charges' => 0,
        ]; // $request->charges

        if (! $charges || ! isset($charges['Admin Charges'])) {
            return $this->error(null, 'Charges not found', 400);
        }

        // Disabled for now.
        // if ($charges['Admin Charges'] == 0) {
        //     return $this->error(null, 'Admin charges cannot be zero', 400);
        // }

        $chargeAmount = $charges['Admin Charges'];

        match ($request->payment_method) {
            PaymentMethod::WALLET => $result = $this->walletPayment($amountPaid, $request, $user, $chargeAmount),
            default => throw new \Exception('Invalid payment method'),
        };

        return $this->processPayment($request, $result, $paymentProcessor);
    }
}
