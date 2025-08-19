<?php

namespace App\Services\Payment;

use App\Enum\ChargeType;
use App\Enum\PaymentType;
use Illuminate\Support\Facades\Auth;

class PaymentDetailService
{
    public static function paystackPayDetails($request, $trip)
    {
        $user = Auth::user();

        if ($user->email === null) {
            return self::error('User email not found, please update your email address', 404);
        }

        $totalAmount = $request->input('amount_paid');

        if ($totalAmount <= 0) {
            return self::error("Amount must be greater than 0");
        }

        $chargesSum = array_sum((array) $request->input('charges'));

        if ($chargesSum != self::getCharges()) {
            return self::error("Charges paid does not match the total charges", 400);
        }

        $amount = ($totalAmount + $chargesSum) * 100;

        $callbackUrl = $request->input('payment_redirect_url');
        if (!filter_var($callbackUrl, FILTER_VALIDATE_URL)) {
            return self::error('Invalid callback URL');
        }

        return [
            'email' => $user->email,
            'amount' => $amount,
            'currency' => "NGN",
            'metadata' => json_encode([
                'user_id' => $user->id,
                'user' => $user,
                'trip_id' => $request->input('trip_id'),
                'trip' => $trip,
                'third_party_booking' => $request->input('third_party_booking'),
                'selected_seat' => $request->input('selected_seat'),
                'trip_type' => $request->input('trip_type'),
                'travelling_with' => $request->input('travelling_with') ?? $request->input('passengers'),
                'third_party_passenger_details' => $request->input('third_party_passenger_details') ?? $request->input('next_of_kin'),
                'payment_method' => $request->input('payment_method'),
                'charges' => $request->input('charges'),
                'payment_type' => PaymentType::TRIP_BOOKING,
            ]),
            'callback_url' => $request->input('payment_redirect_url')
        ];
    }

    private static function error($message, $code = 400)
    {
        return [
			'status' => false,
			'message' => $message,
			'code' => $code
		];
    }

    private static function getCharges()
    {
        $chargeTypes = [
            ChargeType::ADMIN->value,
            ChargeType::VAT->value,
        ];

        $charges = getCharge($chargeTypes);
        return array_sum($charges);
    }
}


