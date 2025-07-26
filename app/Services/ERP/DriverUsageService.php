<?php

namespace App\Services\ERP;

use App\Enum\General;
use App\Enum\TripStatus;
use App\Models\Fee;
use App\Models\Trip;
use App\Services\Admin\AccountService;

class DriverUsageService
{
    public function execute()
    {
        $trips = Trip::with('user.walletAccount')
            ->whereToday('departure_date')
            ->where('status', TripStatus::COMPLETED)
            ->get();

        $fee = Fee::where('name', General::DRIVER_CHARGE)
            ->first() ??
            (object)['amount' => 100]; // Default fee if not found

        $drivers = $trips->pluck('user.id')->unique();

        foreach ($drivers as $driverId) {
            $driver = User::find($driverId);

            if (!$driver) {
                continue; // Skip if the driver doesn't exist
            }

            $this->chargeDriverIfHasWallet($driver, $fee);
        }
    }

    private function chargeDriverIfHasWallet($driver, $fee)
    {
        if ($wallet = $driver->walletAccount) {
            $wallet->decrement('balance', $fee->amount);

            if (app()->environment('production')) {
                app(AccountService::class)->initiateTransfer($fee->amount);
            }

        } else {
            logger()->warning("Driver with ID {$driver->id} does not have a wallet.");
        }
    }
}
