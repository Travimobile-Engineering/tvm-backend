<?php

namespace App\Services\ERP;

use App\Models\Fee;
use App\Enum\General;
use App\DTO\ChargeData;
use App\Enum\ChargeType;
use App\Enum\TransactionTitle;
use Illuminate\Support\Facades\DB;
use App\Services\Admin\AccountService;

class ChargeService
{
    public function smsCharge($user, string $chargeFrom, array $chargeTypes, ?string $source = "wallet")
    {
        $user->loadMissing(['walletAccount']);
        $charges = getCharge($chargeTypes);
        $amount = array_sum($charges);

        $wallet = $user->walletAccount;

        if (! $wallet) {
            logger()->error("User does not have a wallet account.", ['user_id' => $user->id]);
            return;
        }

        if ($source === 'wallet') {
            foreach ($chargeTypes as $type) {
                if ($wallet->balance < $charges[$type]) {
                    logger()->error(
                        "Insufficient wallet balance for {$type} charge.",
                        [
                            'user_id' => $user->id,
                            'wallet_balance' => $wallet->balance,
                            'fee_amount' => $charges[$type],
                            'fee_type' => $type
                        ]
                    );
                    return;
                }
            }
        }

        $this->processCharge(new ChargeData(
            user: $user,
            wallet: $wallet,
            charges: $charges,
            amount: $amount,
            title: TransactionTitle::SMS_CHARGE->value,
            referencePrefix: 'SMS',
            source: $source,
            chargeFrom: $chargeFrom,
            message: 'You have received an SMS charge.'
        ));
    }

    public function adminCharge($user, string $chargeFrom, array $chargeTypes, ?string $source = "wallet")
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

        $this->processCharge(new ChargeData(
            user: $user,
            wallet: $wallet,
            charges: $charges,
            amount: $totalAmount,
            title: TransactionTitle::ADMIN_CHARGE->value,
            referencePrefix: 'ADMIN',
            source: $source,
            chargeFrom: $chargeFrom,
            message: 'You have received system charges.'
        ));
    }

    public function vatCharge($user, string $chargeFrom, array $chargeTypes, ?string $source = 'wallet')
    {
        $user->loadMissing(['walletAccount']);
        $charges = getCharge($chargeTypes);
        $amount = array_sum($charges);

        $wallet = $user->walletAccount;

        if (! $wallet) {
            logger()->error("User does not have a wallet account.", ['user_id' => $user->id]);
            return;
        }

        if ($source === 'wallet') {
            foreach ($chargeTypes as $type) {
                if ($wallet->balance < $charges[$type]) {
                    logger()->error(
                        "Insufficient wallet balance for {$type} charge.",
                        [
                            'user_id' => $user->id,
                            'wallet_balance' => $wallet->balance,
                            'fee_amount' => $charges[$type],
                            'fee_type' => $type
                        ]
                    );
                    return;
                }
            }
        }

        $this->processCharge(new ChargeData(
            user: $user,
            wallet: $wallet,
            charges: $charges,
            amount: $amount,
            title: TransactionTitle::VAT_CHARGE->value,
            referencePrefix: 'VAT',
            source: $source,
            chargeFrom: $chargeFrom,
            message: 'You have received system charges.'
        ));
    }

    public function withdrawalCharge($charges)
    {
        app(AccountService::class)->initiateTransfer($charges);
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

    private function processCharge(ChargeData $data)
    {
        DB::transaction(function () use ($data) {
            if ($data->source === 'wallet') {
                $data->wallet->decrement($data->chargeFrom, $data->amount);

                $reference = generateReference($data->referencePrefix, 'transactions');

                if ($data->chargeFrom === 'balance') {
                    $data->user->createTransaction(
                        $data->title,
                        $data->amount,
                        'DR',
                        $reference,
                        null,
                        $data->message ?? "You have received a {$data->referencePrefix} charge."
                    );
                } else {
                    $data->user->createEarning(
                        $data->title,
                        $data->amount,
                        'DR',
                        General::PAID,
                        'You have received system charges.'
                    );
                }
            }

            app(AccountService::class)->initiateTransfer($data->charges);
        });
    }
}
