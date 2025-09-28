<?php

namespace App\Trait;

use App\Enum\ChargeType;
use App\Models\User;
use App\Services\ERP\ChargeService;

trait ChargeTrait
{
    use HttpResponse;

    public function bookingCharge($userId)
    {
        $user = User::find($userId);

        if (! $user) {
            return $this->error('User not found.', 404);
        }

        $chargeTypes = [
            ChargeType::ADMIN->value,
            ChargeType::VAT->value,
        ];

        if ($user->inbox_notifications) {
            $chargeTypes[] = ChargeType::SMS->value;
        }

        return $this->success(getCharge($chargeTypes), 'Booking charges retrieved successfully.');
    }

    public function bankWithdrawalCharge()
    {
        $chargeTypes = [
            ChargeType::ADMIN->value,
            ChargeType::WITHDRAW_FEE->value,
        ];

        return $this->success(getCharge($chargeTypes), 'Bank withdrawal charges retrieved successfully.');
    }

    public function walletWithdrawalCharge()
    {
        $chargeTypes = [
            ChargeType::ADMIN->value,
        ];

        return $this->success(getCharge($chargeTypes), 'Wallet withdrawal charges retrieved successfully.');
    }

    protected function recordCharges($request, $user, ?string $chargeFrom = 'balance'): void
    {
        $charges = $request->charges ?? [];

        foreach ($charges as $type => $amount) {
            if ($amount <= 0) {
                continue; // skip zero charges
            }

            match ($type) {
                ChargeType::ADMIN->value => app(ChargeService::class)->adminCharge($user, $chargeFrom, [$type], 'wallet'),
                ChargeType::VAT->value => app(ChargeService::class)->vatCharge($user, $chargeFrom, [$type], 'wallet'),
                ChargeType::SMS->value => app(ChargeService::class)->smsCharge($user, $chargeFrom, [$type], 'wallet'),
                ChargeType::WITHDRAW_FEE->value => app(ChargeService::class)->withdrawalCharge([$type => $amount]),
                default => logger()->warning("Unknown charge type: {$type}", [
                    'user_id' => $user->id,
                ]),
            };
        }
    }
}
