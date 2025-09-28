<?php

namespace App\Trait;

trait PaymentLogTrait
{
    public function logPayment($user, $event, $type, $tripId = null)
    {
        $paymentData = $event['data'];

        $channel = $paymentData['channel'];
        $currency = $paymentData['currency'];
        $ip_address = $paymentData['ip_address'];
        $paid_at = $paymentData['paid_at'];
        $createdAt = $paymentData['created_at'];
        $transaction_date = $paymentData['paid_at'];
        $amount = $paymentData['amount'];
        $formattedAmount = number_format($amount / 100, 2, '.', '');
        $ref = $paymentData['reference'];
        $status = $paymentData['status'];

        return $user->paymentLogs()->create([
            'trip_id' => $tripId,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'phone_number' => $user->phone_number,
            'amount' => $formattedAmount,
            'reference' => $ref,
            'channel' => $channel,
            'currency' => $currency,
            'ip_address' => $ip_address,
            'paid_at' => $paid_at,
            'createdAt' => $createdAt,
            'transaction_date' => $transaction_date,
            'status' => $status,
            'type' => $type,
        ]);
    }

    public function walletPaymentLog($user, $request, $amount_paid, $ref, $type)
    {
        return $user->paymentLogs()->create([
            'trip_id' => $request->trip_id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'phone_number' => $user->phone_number,
            'amount' => $amount_paid,
            'reference' => $ref,
            'channel' => 'wallet',
            'currency' => 'NGN',
            'ip_address' => $request->ip(),
            'paid_at' => now(),
            'createdAt' => now(),
            'transaction_date' => now(),
            'status' => 'success',
            'type' => $type,
        ]);
    }
}
