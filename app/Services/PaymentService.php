<?php

namespace App\Services;

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

        if (!isset($event['event']) || !isset($event['data'])) {
            return $this->error(null, 'Invalid payload', 400);
        }

        (new PaystackEventHandler($event))->handle($event);

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


