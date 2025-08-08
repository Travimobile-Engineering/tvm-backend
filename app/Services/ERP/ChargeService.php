<?php

namespace App\Services\ERP;

use App\Models\Fee;
use App\Enum\ChargeType;
use Illuminate\Support\Facades\DB;
use App\Services\Admin\AccountService;

class ChargeService
{
    public function smsCharge($user)
    {
        $user->loadMissing(['walletAccount']);
        $feeAmount = Fee::where('name', ChargeType::SMS->value)->value('amount') ?? 4.00;

        $wallet = $user->walletAccount;

        if (! $wallet) {
            logger()->error("User does not have a wallet account.", ['user_id' => $user->id]);
            return;
        }

        if ($wallet->balance < $feeAmount) {
            logger()->error(
                "Insufficient wallet balance for SMS charge.",
                ['user_id' => $user->id, 'wallet_balance' => $wallet->balance, 'fee_amount' => $feeAmount]
            );
            return;
        }

        DB::transaction(function () use ($wallet, $feeAmount, $user) {
            $wallet->decrement('balance', $feeAmount);
            app(AccountService::class)->initiateTransfer($feeAmount);

            $reference = generateReference('SMS', 'transactions');
            $user->createTransaction(TransactionTitle::SMS_CHARGE->value, $feeAmount, 'DR', $reference);
        });
    }

    public function adminCharge($user)
    {
        $user->loadMissing(['walletAccount']);
        $feeAmount = Fee::where('name', ChargeType::ADMIN->value)->value('amount') ?? 10.00;

        $wallet = $user->walletAccount;

        if (! $wallet) {
            logger()->error("User does not have a wallet account.", ['user_id' => $user->id]);
            return;
        }

        if ($wallet->balance < $feeAmount) {
            logger()->error(
                "Insufficient wallet balance for Admin charge.",
                ['user_id' => $user->id, 'wallet_balance' => $wallet->balance, 'fee_amount' => $feeAmount]
            );
            return;
        }

        DB::transaction(function () use ($wallet, $feeAmount, $user) {
            $wallet->decrement('balance', $feeAmount);
            app(AccountService::class)->initiateTransfer($feeAmount);

            $reference = generateReference('ADMIN', 'transactions');
            $user->createTransaction(TransactionTitle::ADMIN_CHARGE->value, $feeAmount, 'DR', $reference);
        });
    }

    public function vatCharge($user)
    {
        $user->loadMissing(['walletAccount']);
        $feeAmount = Fee::where('name', ChargeType::VAT->value)->value('amount') ?? 20.00;

        $wallet = $user->walletAccount;

        if (! $wallet) {
            logger()->error("User does not have a wallet account.", ['user_id' => $user->id]);
            return;
        }

        if ($wallet->balance < $feeAmount) {
            logger()->error(
                "Insufficient wallet balance for VAT charge.",
                ['user_id' => $user->id, 'wallet_balance' => $wallet->balance, 'fee_amount' => $feeAmount]
            );
            return;
        }

        DB::transaction(function () use ($wallet, $feeAmount, $user) {
            $wallet->decrement('balance', $feeAmount);
            app(AccountService::class)->initiateTransfer($feeAmount);

            $reference = generateReference('VAT', 'transactions');
            $user->createTransaction(TransactionTitle::VAT_CHARGE->value, $feeAmount, 'DR', $reference);
        });
    }

    public function insuranceCharge()
    {}

    public function unionCharge()
    {}
}
