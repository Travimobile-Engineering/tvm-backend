<?php

namespace App\Services;

use App\Enum\PaymentType;
use App\Enum\PaystackEvent;
use App\Trait\HttpResponse;
use App\Trait\PaymentTrait;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    use HttpResponse, PaymentTrait;

    public function webhook($request)
    {
        $secretKey = config('paystack.secretKey');
        $signature = $request->header('x-paystack-signature');
        $payload = $request->getContent();

        if (!$signature || $signature !== hash_hmac('sha512', $payload, $secretKey)) {
            return $this->error(null, 'Invalid signature', 400);
        }

        $event = json_decode($payload, true);

        if (isset($event['event']) && $event['event'] === PaystackEvent::CHARGE_SUCCESS) {
            $data = $event['data'];
            $paymentType = $data['metadata']['payment_type'];

            switch ($paymentType) {
                case PaymentType::FUND_WALLET:
                    $this->handleFundWallet($event);
                    break;

                case PaymentType::TRIP_BOOKING:
                    $this->handleTripBooking($event);
                    break;

                case PaymentType::PREMIUM_HIRE:
                    $this->handlePremiumHire($event);
                    break;

                default:
                    Log::warning('Unknown payment type', ['payment_type' => $paymentType]);
                    break;
            }
        }

        return response()->json(['status' => true], 200);
    }
}


