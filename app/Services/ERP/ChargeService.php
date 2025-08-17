<?php

namespace App\Services\ERP;

use App\Models\Fee;
use App\Enum\ChargeType;
use App\Enum\TransactionTitle;
use Illuminate\Support\Facades\DB;
use App\Services\Admin\AccountService;

class ChargeService
{
    public function smsCharge($user, array $chargeTypes)
    {
        $user->loadMissing(['walletAccount']);
        $charges = getCharge($chargeTypes);
        $amount = array_sum($charges);

        $wallet = $user->walletAccount;

        if (! $wallet) {
            logger()->error("User does not have a wallet account.", ['user_id' => $user->id]);
            return;
        }

        foreach ($chargeTypes as $type) {
            if ($wallet->balance < $charges[$type]) {
                logger()->error(
                    "Insufficient wallet balance for {$type} charge.",
                    [
                        'user_id'        => $user->id,
                        'wallet_balance' => $wallet->balance,
                        'fee_amount'     => $charges[$type],
                        'fee_type'       => $type
                    ]
                );
                return;
            }
        }

        DB::transaction(function () use ($wallet, $charges, $user, $amount) {
            $wallet->decrement('balance', $amount);

            app(AccountService::class)->initiateTransfer($charges);

            $reference = generateReference('SMS', 'transactions');
            $user->createTransaction(
                TransactionTitle::SMS_CHARGE->value,
                $amount,
                'DR',
                $reference,
                null,
                'You have received an SMS charge.'
            );
        });
    }

    public function adminCharge($user, string $chargeFrom, array $chargeTypes)
    {
        $user->loadMissing(['walletAccount']);
        $charges = getCharge($chargeTypes);

        // Calculate total fee (sum of all charges + extra charge)
        $totalAmount = array_sum($charges);

        $wallet = $user->walletAccount;

        if (! $wallet) {
            logger()->error("User does not have a wallet account.", ['user_id' => $user->id]);
            return;
        }

        DB::transaction(function () use ($wallet, $charges, $user, $chargeFrom, $totalAmount) {
            $wallet->decrement($chargeFrom, $totalAmount);

            app(AccountService::class)->initiateTransfer($charges);

            $reference = generateReference('ADMIN', 'transactions');

            if ($chargeFrom === 'balance') {
                $user->createTransaction(
                    TransactionTitle::ADMIN_CHARGE->value,
                    $totalAmount,
                    'DR',
                    $reference,
                    null,
                    'You have received system charges.'
                );
            } else {
                $user->createEarning(
                    TransactionTitle::ADMIN_CHARGE->value,
                    $totalAmount,
                    'DR',
                    General::PAID,
                    'You have received system charges.'
                );
            }
        });
    }

    public function vatCharge($user, array $chargeTypes)
    {
        $user->loadMissing(['walletAccount']);
        $charges = getCharge($chargeTypes);
        $amount = array_sum($charges);

        $wallet = $user->walletAccount;

        if (! $wallet) {
            logger()->error("User does not have a wallet account.", ['user_id' => $user->id]);
            return;
        }

        foreach ($chargeTypes as $type) {
            if ($wallet->balance < $charges[$type]) {
                logger()->error(
                    "Insufficient wallet balance for {$type} charge.",
                    [
                        'user_id'        => $user->id,
                        'wallet_balance' => $wallet->balance,
                        'fee_amount'     => $charges[$type],
                        'fee_type'       => $type
                    ]
                );
                return;
            }
        }

        DB::transaction(function () use ($wallet, $charges, $user, $amount) {
            $wallet->decrement('balance', $amount);

            app(AccountService::class)->initiateTransfer($charges);

            $reference = generateReference('VAT', 'transactions');
            $user->createTransaction(
                TransactionTitle::VAT_CHARGE->value,
                $amount,
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
