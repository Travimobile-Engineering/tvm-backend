<?php

namespace App\Services\ERP;

use App\Enum\General;
use App\Enum\TripStatus;
use App\Enum\UserType;
use App\Models\Fee;
use App\Models\Trip;
use App\Models\User;
use App\Models\UserCharge;
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
        $today = now()->toDateString(); // Get today's date in Y-m-d format
        $alreadyCharged = UserCharge::where('user_id', $driver->id)
            ->where('date', $today)
            ->where('user_category', UserType::DRIVER->value)
            ->exists();

        // If the driver has already been charged today, skip the charge
        if ($alreadyCharged) {
            logger()->info("Driver with ID {$driver->id} has already been charged today.");
            return;
        }

        if ($wallet = $driver->walletAccount) {
            $wallet->decrement('balance', $fee->amount);

            UserCharge::create([
                'user_id' => $driver->id,
                'type' => General::DRIVER_CHARGE,
                'date' => $today,
                'amount' => $fee->amount,
                'user_category' => UserType::DRIVER->value,
            ]);

            if (app()->environment(['production', 'staging'])) {
                app(AccountService::class)->initiateTransfer($fee->amount);
            }

        } else {
            logger()->warning("Driver with ID {$driver->id} does not have a wallet.");
        }
    }
}
