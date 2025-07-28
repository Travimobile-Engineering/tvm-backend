<?php

namespace App\Services\ERP;

use App\Models\AgentCommission;
use App\Models\Commission;
use App\Models\User;


class AgentCommissionService
{
    /**
     * Distribute the commission between the first agent and the current agent.
     *
     * @param User $passenger
     * @param User $agent
     * @return void
     */
    public function distributeAgentCommission(User $passenger, User $agent)
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
            $this->createFirstTimeBookingCommission($passenger, $agent, $primaryCommission);
        } else {
            // If it's a subsequent booking, process commission for both the first agent and the current agent
            $this->createSubsequentBookingCommission($passenger, $agent, $commissionRecord, $secondaryCommission);
        }
    }

    /**
     * Handle the commission distribution for the first-time booking.
     *
     * @param User $passenger
     * @param User $agent
     * @param $primaryCommission
     */
    private function createFirstTimeBookingCommission(User $passenger, User $agent, $primaryCommission)
    {
        Commission::create([
            'agent_id' => $agent->id,    // Current agent earns full commission
            'passenger_id' => $passenger->id,
            'amount' => $primaryCommission->amount,
            'is_first_time' => true,
            'first_agent_id' => $agent->id, // The first agent is the current one
        ]);

        // Top up the agent's earnings
        $this->topUpEarnings($agent, $primaryCommission->amount);
    }

    /**
     * Handle the commission distribution for subsequent bookings.
     *
     * @param User $passenger
     * @param User $agent
     * @param $commissionRecord
     * @param $secondaryCommission
     */
    private function createSubsequentBookingCommission(User $passenger, User $agent, $commissionRecord, $secondaryCommission)
    {
        $firstAgent = $commissionRecord->firstAgent; // The first agent who booked this passenger

        // If it's the same agent as the first one, only one commission record for the current agent
        if ($firstAgent->id === $agent->id) {
            $this->createCommission($agent, $passenger, $secondaryCommission);
        } else {
            // If it's a different agent, create two commission records
            $this->createCommission($firstAgent, $passenger, $secondaryCommission); // First agent
            $this->createCommission($agent, $passenger, $secondaryCommission); // Current agent
        }
    }

    /**
     * Create commission record for an agent.
     *
     * @param User $agent
     * @param User $passenger
     * @param $secondaryCommission
     */
    private function createCommission(User $agent, User $passenger, $secondaryCommission)
    {
        Commission::create([
            'agent_id' => $agent->id,
            'passenger_id' => $passenger->id,
            'amount' => $secondaryCommission->amount,
            'is_first_time' => false,
            'first_agent_id' => $passenger->commissionsAsPassenger->first()->firstAgent->id, // First agent remains the same
        ]);

        // Top up the agent's earnings
        $this->topUpEarnings($agent, $secondaryCommission->amount);
    }

    /**
     * Top up the earnings of the agent.
     *
     * @param User $agent
     * @param int $amount
     */
    private function topUpEarnings(User $agent, $amount)
    {
        $agent->walletAccount()->increment('earnings', $amount);
    }
}
