<?php

namespace App\Services\Payment;

use App\Enum\PaymentType;
use Illuminate\Support\Facades\Auth;

class PaymentDetailService
{
    public static function paystackPayDetails($request, $trip)
    {
        $user = Auth::user();
        $amount = $request->input('amount_paid') * 100;

        $callbackUrl = $request->input('payment_redirect_url');
        if (!filter_var($callbackUrl, FILTER_VALIDATE_URL)) {
            return response()->json(['error' => 'Invalid callback URL'], 400);
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
                'payment_type' => PaymentType::TRIP_BOOKING,
            ]),
            'callback_url' => $request->input('payment_redirect_url')
        ];
    }
}


