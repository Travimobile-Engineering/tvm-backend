<?php

namespace App\Trait;

use App\Enum\PaymentType;
use App\Enum\TransitCompanyType;
use App\Models\TransitCompany;

trait DriverTrait
{
    use HttpResponse;

    const TRIP_CHARGE_AMOUNT = 1000;

    protected function createTransitCompany($user, $request)
    {
        return TransitCompany::create([
            'user_id' => $user->id,
            'name' => $user->first_name,
            'email' => $user->email,
            'phone' => $user->phone_number ?? 0,
            'union_id' => $request->transit_company_union_id,
            'union_states_chapter' => $request->union_states_chapter,
            'park' => $request->park,
            'type' => TransitCompanyType::INDIVIDUAL,
            'ver_code' => 0,
        ]);
    }

    protected function topUpWallet($user)
    {
        $pendingAmount = $user->driverTripPayments->where('status', 'pending')->sum('amount');

        if ($pendingAmount > 0) {
            $user->increment('wallet', $pendingAmount);

            $user->driverTripPayments->where('status', 'pending')->each(function ($payment) {
                $payment->update(['status' => 'paid']);
            });

            $title = "Credit wallet from pending balance";
            $type = PaymentType::CR;

            $this->createTransaction($user, $pendingAmount, $title, $type);
        }
    }

    protected function chargeWallet($user, $amount = null)
    {
        $user->wallet -= $amount ?? self::TRIP_CHARGE_AMOUNT;
        $user->save();

        $title = "Wallet charged for manifest";
        $type = PaymentType::DR;

        $this->createTransaction($user, self::TRIP_CHARGE_AMOUNT, $title, $type);
    }

    protected function createTransaction($user, $amount, $title, $type)
    {
        $user->transactions()->create([
            'title' => $title,
            'amount' => $amount,
            'type' => $type,
            'txn_reference' => getRandomNumber()
        ]);
    }

}



