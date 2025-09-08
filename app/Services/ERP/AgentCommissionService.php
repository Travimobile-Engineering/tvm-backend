<?php

namespace App\Services\ERP;

use App\Models\User;
use App\Enum\ChargeType;
use App\Models\Commission;
use App\Enum\PaymentStatus;
use App\Enum\CommissionEnum;
use App\Enum\TransactionTitle;
use App\Models\AgentCommission;
use Illuminate\Support\Facades\DB;
use App\Services\Admin\AccountService;
use App\Services\ERP\CommissionBreakdownService;

class AgentCommissionService
{
    public function __construct(
        protected CommissionBreakdownService $commissionBreakdownService
    ) {}

    /**
     * Distribute the commission between the first agent and the current agent.
     *
     * @param User $passenger
     * @param User $agent
     * @param int $passengerCount
     * @param string|null $bookingId
     * @return void
     */
    public function distributeAgentCommission(User $passenger, User $agent, int $passengerCount, ?string $bookingId = null)
    {
        // Retrieve primary commission (for the first agent)
        $primaryCommission = AgentCommission::where('type', AgentCommission::PRIMARY)
                                            ->first();

        // Retrieve secondary commission (for subsequent bookings)
        $secondaryCommission = AgentCommission::where('type', AgentCommission::SECONDARY)
                                              ->first();

        if (!$primaryCommission || !$secondaryCommission) {
            // Handle case where commission records are not found
            throw new \Exception("Commission records not found.");
        }

        // Check if the passenger has any previous commission records
        $commissionRecord = $passenger->commissionsAsPassenger()->first(); // Get the first commission record for the passenger

        // If it's the first-time booking, process the full commission for the current agent
        if (!$commissionRecord) {
            $this->createFirstTimeBookingCommission($passenger, $agent, $primaryCommission, $passengerCount, $bookingId);
        } else {
            // If it's a subsequent booking, process commission for both the first agent and the current agent
            $this->createSubsequentBookingCommission($passenger, $agent, $commissionRecord, $secondaryCommission, $passengerCount, $bookingId);
        }
    }

    /**
     * Handle the commission distribution for the first-time booking.
     *
     * @param User $passenger
     * @param User $agent
     * @param $primaryCommission
     */
    private function createFirstTimeBookingCommission(User $passenger, User $agent, $primaryCommission, int $passengerCount, $bookingId)
    {
        $amount = $primaryCommission->amount * $passengerCount;

        Commission::create([
            'agent_id' => $agent->id, // Current agent earns full commission
            'passenger_id' => $passenger->id,
            'amount' => $amount,
            'is_first_time' => true,
            'first_agent_id' => $agent->id,
        ]);

        // Get the breakdown of the commission
        $breakdown = $this->commissionBreakdownService->getBreakdown(
            $amount,
            CommissionEnum::BOOKING_AGENT_PERCENT->value,
            CommissionEnum::BOOKING_COMPANY_PERCENT->value
        );

        // Top up the agent's earnings
        $this->topUpEarnings($agent, $breakdown['agent_share'], $bookingId);

        // Send the company share to the company account
        app(AccountService::class)->initiateTransfer([
            ChargeType::AGENT_COMMISSION->value => $breakdown['company_share'],
        ]);
    }

    /**
     * Handle the commission distribution for subsequent bookings.
     *
     * @param User $passenger
     * @param User $agent
     * @param $commissionRecord
     * @param $secondaryCommission
     */
    private function createSubsequentBookingCommission(
        User $passenger,
        User $agent,
        $commissionRecord,
        $secondaryCommission,
        int $passengerCount,
        $bookingId
    )
    {
        $firstAgent = $commissionRecord->firstAgent; // The first agent who booked this passenger

        // If it's the same agent as the first one, only one commission record for the current agent
        if ($firstAgent->id === $agent->id) {
            $this->createCommission($agent, $passenger, $secondaryCommission, $passengerCount, $bookingId);
        } else {
            // If it's a different agent, create two commission records
            $this->createCommission($firstAgent, $passenger, $secondaryCommission, $passengerCount, $bookingId); // First agent
            $this->createCommission($agent, $passenger, $secondaryCommission, $passengerCount, $bookingId); // Current agent
        }
    }

    /**
     * Create commission record for an agent.
     *
     * @param User $agent
     * @param User $passenger
     * @param $secondaryCommission
     */
    private function createCommission(User $agent, User $passenger, $secondaryCommission, int $passengerCount, $bookingId)
    {
        $amount = $secondaryCommission->amount * $passengerCount;

        Commission::create([
            'agent_id' => $agent->id,
            'passenger_id' => $passenger->id,
            'amount' => $amount,
            'is_first_time' => false,
            'first_agent_id' => $passenger->commissionsAsPassenger->first()->firstAgent->id, // First agent remains the same
        ]);

        // Get the breakdown of the commission
        $breakdown = $this->commissionBreakdownService->getBreakdown(
            $amount,
            CommissionEnum::BOOKING_AGENT_PERCENT->value,
            CommissionEnum::BOOKING_COMPANY_PERCENT->value
        );

        // Top up the agent's earnings
        $this->topUpEarnings($agent, $breakdown['agent_share'], $bookingId);

        // Send the company share to the company account
        app(AccountService::class)->initiateTransfer([
            ChargeType::AGENT_COMMISSION->value => $breakdown['company_share'],
        ]);
    }

    /**
     * Top up the earnings of the agent.
     *
     * @param User $agent
     * @param int $amount
     */
    private function topUpEarnings(User $agent, $amount, $bookingId)
    {
        DB::transaction(function () use ($agent, $amount, $bookingId) {
            // Ensure the amount is a valid number (float or int)
            $amount = (float) $amount;

            if ($amount <= 0) {
                throw new \Exception("Invalid amount for earnings increment.");
            }

            $wallet = $agent->walletAccount;

            if (!$wallet) {
                throw new \Exception("Agent wallet account not found.");
            }

            $wallet->increment('earnings', $amount);

            $description = "Agent commission for booking ID: {$bookingId}";

            $agent->createEarning(TransactionTitle::AGENT_COMMISSION->value, $amount, 'CR', PaymentStatus::PAID->value, $description);
        });
    }
}
