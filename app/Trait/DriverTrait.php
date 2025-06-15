<?php

namespace App\Trait;

use App\Enum\PaymentType;
use App\Enum\TransitCompanyType;
use App\Models\TransitCompany;
use App\Services\Admin\AccountService;

trait DriverTrait
{
    use HttpResponse;

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
        $amount = $amount ? $amount : getFee('manifest');
        $user->wallet -= $amount;
        $user->save();

        $title = "Wallet charged for manifest";
        $type = PaymentType::DR;

        $this->createTransaction($user, $amount, $title, $type);
    }

    protected function createTransaction($user, $amount, $title, $type)
    {
        $user->transactions()->create([
            'title' => $title,
            'amount' => $amount,
            'type' => $type,
            'txn_reference' => getRandomNumber()
        ]);

        if (app()->environment('production')) {
            app(AccountService::class)->initiateTransfer($amount);
        }
    }

    protected function uploadInteriorImages($request, $user)
    {
        $interiorImages = uploadFilesBatch(
            $request->file('vehicle_interior_images'),
            'driver/vehicle/interior'
        );

        foreach ($interiorImages as $image) {
            if ($image['url'] !== null) {
                $user->vehicle->vehicleImages()->create([
                    'type' => 'interior',
                    'url' => $image['url'],
                    'public_id' => $image['public_id'],
                ]);
            }
        }
    }

    protected function uploadExteriorImages($request, $user)
    {
        $exteriorImages = uploadFilesBatch(
            $request->file('vehicle_exterior_images'),
            'driver/vehicle/exterior'
        );

        foreach ($exteriorImages as $image) {
            if ($image['url'] !== null) {
                $user->vehicle->vehicleImages()->create([
                    'type' => 'exterior',
                    'url' => $image['url'],
                    'public_id' => $image['public_id'],
                ]);
            }
        }
    }

}



