<?php

namespace App\Services\ERP;

use App\Enum\CommissionEnum;
use App\Enum\General;
use App\Enum\PaymentStatus;
use App\Enum\PaymentType;
use App\Enum\TransactionTitle;
use App\Enum\TripStatus;
use App\Enum\UserType;
use App\Models\Fee;
use App\Models\Trip;
use App\Models\UserCharge;
use App\Services\Admin\AccountService;
use App\Services\ERP\CommissionBreakdownService;
use Illuminate\Support\Facades\DB;

class DriverUsageService
{
    public function __construct(
        protected CommissionBreakdownService $commissionBreakdownService
    ) {}

    public function execute()
    {
        $trips = Trip::with(['user.walletAccount', 'agent.walletAccount'])
            ->whereToday('departure_date')
            ->where('status', TripStatus::COMPLETED)
            ->get();

        $fee = Fee::where('name', General::DRIVER_CHARGE)
            ->first() ??
            (object)['amount' => 100]; // Default fee if not found

        foreach ($trips as $trip) {
            $driver = $trip->user;

            // If the driver doesn't exist, skip the charge
            if (! $driver) {
                logger()->warning("Trip ID {$trip->id} has no valid driver.");
                continue;
            }

            $this->chargeDriverIfHasWallet($driver, $fee, $trip);
        }
    }

    private function chargeDriverIfHasWallet($driver, $fee, $trip)
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

        DB::transaction(function () use ($driver, $fee, $trip, $today) {

            $wallet = $driver->walletAccount;

            if (! $wallet) {
                logger()->warning("Driver with ID {$driver->id} does not have a wallet.");
                return;
            }

            // Decrement total fee from driver
            $wallet->decrement('balance', $fee->amount);

            // Create the driver charge transactions
            $this->createDriverChargeTransaction($driver, $fee, $today);

            $breakdown = $this->commissionBreakdownService->getBreakdown(
                $fee->amount,
                CommissionEnum::ERP_AGENT_PERCENT->value,
                CommissionEnum::ERP_COMPANY_PERCENT->value
            );

            $agent = $trip->agent;

            if ($agent && $agent->walletAccount) {
                // Increment the agent's earnings
                $agent->walletAccount->increment('earnings', $breakdown['agent_share']);

                // Create the agent commission earning
                $agent->createEarning(
                    TransactionTitle::AGENT_COMMISSION->value,
                    $breakdown['agent_share'],
                    PaymentType::CR,
                    PaymentStatus::PAID->value
                );

                logger()->info("Credited agent ID {$agent->id} with ₦{$breakdown['agent_share']} from trip ID {$trip->id}.");
            } else {
                logger()->info("No agent found or agent has no wallet for trip ID {$trip->id}.");
            }

            // Company share
            if (app()->environment(['production', 'staging'])) {
                app(AccountService::class)->initiateTransfer($breakdown['company_share']);
            }
        });
    }

    private function createDriverChargeTransaction($driver, $fee, $today)
    {
        // Save the charge record
        UserCharge::create([
            'user_id' => $driver->id,
            'type' => General::DRIVER_CHARGE,
            'date' => $today,
            'amount' => $fee->amount,
            'user_category' => UserType::DRIVER->value,
        ]);

        // Create the driver charge transaction
        $driver->createTransaction(
            TransactionTitle::DRIVER_CHARGE->value,
            $fee->amount,
            PaymentType::DR,
            "wallet"
        );
    }
}
