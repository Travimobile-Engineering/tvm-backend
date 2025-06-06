<?php

namespace App\Services;

use App\Enum\PaymentType;
use App\Enum\PaystackEvent;
use App\Services\Paystack\PaystackEventHandler;
use App\Trait\HttpResponse;
use App\Trait\PaymentTrait;
use App\Trait\Transfer;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    use HttpResponse, PaymentTrait, Transfer;

    public function webhook($request)
    {
        $secretKey = config('paystack.secretKey');
        $signature = $request->header('x-paystack-signature');
        $payload = $request->getContent();

        if (!$signature || $signature !== hash_hmac('sha512', $payload, $secretKey)) {
            return $this->error(null, 'Invalid signature', 400);
        }

        $event = json_decode($payload, true);

        // if (isset($event['event']) && $event['event'] === PaystackEvent::CHARGE_SUCCESS) {
        //     $data = $event['data'];
        //     $paymentType = $data['metadata']['payment_type'];

        //     switch ($paymentType) {
        //         case PaymentType::FUND_WALLET:
        //             $this->handleFundWallet($event);
        //             break;

        //         case PaymentType::TRIP_BOOKING:
        //             $alreadyProcessed = $this->isAlreadyProcessed($event);
        //             if ($alreadyProcessed) {
        //                 return response()->json(['status' => true, 'message' => 'Already processed'], 200);
        //             }

        //             $this->handleTripBooking($event);
        //             break;

        //         case PaymentType::PREMIUM_HIRE:
        //             $this->handlePremiumHire($event);
        //             break;

        //         default:
        //             Log::warning('Unknown payment type', ['payment_type' => $paymentType]);
        //             break;
        //     }
        // }

        if (!isset($event['event']) || !isset($event['data'])) {
            return $this->error(null, 'Invalid payload', 400);
        }

        PaystackEventHandler::handle($event);

        return response()->json(['status' => true], 200);
    }

    public function approveTransfer($request)
    {
        $payload = json_decode($request->getContent(), true);

        $transfers = data_get($payload, 'data.transfers', []);

        if (empty($transfers)) {
            Log::warning('No transfers found in approval payload:', $payload);
            return response()->json(['message' => 'Invalid transfer request'], 400);
        }

        foreach ($transfers as $transfer) {
            $isValid = $this->isValidTransferRequest([
                'reference' => $transfer['reference'] ?? null,
                'amount' => $transfer['amount'] ?? null,
                'recipient' => $transfer['recipient']['recipientCode'] ?? null,
            ]);

            if (! $isValid) {
                return response()->json(['message' => 'Invalid transfer request'], 400);
            }
        }

        return response()->json(['message' => 'Transfer approved'], 200);
    }

}


