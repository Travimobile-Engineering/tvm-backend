<?php

namespace App\Trait;

use App\Models\User;
use App\Enum\ChargeType;

trait ChargeTrait
{
    use HttpResponse;

    public function bookingCharge($userId)
    {
        $user = User::find($userId);

        if (! $user) {
            return $this->error("User not found.", 404);
        }

        $chargeTypes = [
            ChargeType::ADMIN->value,
            ChargeType::VAT->value,
        ];

        if ($user->inbox_notifications) {
            $chargeTypes[] = ChargeType::SMS->value;
        }

        return $this->success(getCharge($chargeTypes), "Booking charges retrieved successfully.");
    }

    public function bankWithdrawalCharge()
    {
        $chargeTypes = [
            ChargeType::ADMIN->value,
            ChargeType::WITHDRAW_FEE->value,
        ];

        return $this->success(getCharge($chargeTypes), "Bank withdrawal charges retrieved successfully.");
    }

    public function walletWithdrawalCharge()
    {
        $chargeTypes = [
            ChargeType::ADMIN->value,
        ];

        return $this->success(getCharge($chargeTypes), "Wallet withdrawal charges retrieved successfully.");
    }
}
