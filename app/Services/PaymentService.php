<?php

namespace App\Services;

use App\Enum\PaymentType;
use App\Enum\PaystackEvent;
use App\Models\User;
use App\Trait\HttpResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    use HttpResponse;

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

                case '':
                    return "hello";
                    break;

                default:
                    Log::warning('Unknown payment type', ['payment_type' => $paymentType]);
                    break;
            }
        }

        return response()->json(['status' => true], 200);
    }

    private function handleFundWallet($event)
    {
        $paymentData = $event['data'];
        $userId = $paymentData['metadata']['user_id'];
        $amount = $paymentData['amount'];
        $ref = $paymentData['reference'];

        try {
            $user = User::with('transactions')->findOrFail($userId);

            DB::beginTransaction();

            $user->update([
                'wallet' => $user->wallet + $amount
            ]);

            $user->transactions()->create([
                'title' => PaymentType::FUND_WALLET,
                'amount' => $amount,
                'type' => PaymentType::CR,
                'txn_reference' => $ref
            ]);

            DB::commit();
            info("User with ID: {$user->id} topped up wallet with amount: {$amount}");
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }
}


