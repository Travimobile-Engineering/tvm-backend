<?php

namespace App\Services\ERP;

use App\Models\Fee;
use App\Enum\ChargeType;
use App\Enum\TransactionTitle;
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
            $user->createTransaction(
                TransactionTitle::SMS_CHARGE->value,
                $feeAmount,
                'DR',
                $reference,
                null,
                'You have received an SMS charge.'
            );
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
            $user->createTransaction(
                TransactionTitle::ADMIN_CHARGE->value,
                $feeAmount,
                'DR',
                $reference,
                null,
                'You have received an admin charge.'
            );
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
            $user->createTransaction(
                TransactionTitle::VAT_CHARGE->value,
                $feeAmount,
                'DR',
                $reference,
                null,
                'You have received a VAT charge.'
            );
        });
    }

    public function insuranceCharge()
    {
        $feeAmount = Fee::where('name', ChargeType::INSURANCE->value)->value('amount') ?? 200.00;
    }

    public function unionCharge($user)
    {
        $user->loadMissing(['walletAccount', 'transitCompany']);
        $feeAmount = Fee::where('name', ChargeType::UNION->value)->value('amount') ?? 50.00;

        $wallet = $user->walletAccount;
        $union = $user->transitCompany;

        if (! $wallet) {
            logger()->error("User does not have a wallet account.", ['user_id' => $user->id]);
            return;
        }

        if (! $union) {
            logger()->error("User does not have a transit company.", ['user_id' => $user->id]);
            return;
        }

        if ($wallet->balance < $feeAmount) {
            logger()->error(
                "Insufficient wallet balance for Union Remittance charge.",
                ['user_id' => $user->id, 'wallet_balance' => $wallet->balance, 'fee_amount' => $feeAmount]
            );
            return;
        }

        DB::transaction(function () use ($wallet, $feeAmount, $user, $union) {
            $wallet->decrement('balance', $feeAmount);
            //app(AccountService::class)->initiateTransfer($feeAmount);

            $reference = generateReference('UNION', 'transactions');
            $user->createTransaction(
                TransactionTitle::UNION_CHARGE->value,
                $feeAmount,
                'DR',
                $reference,
                null,
                'You have received a Union Remittance charge.'
            );
        });
    }
}
