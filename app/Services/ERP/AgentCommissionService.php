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
     * @param User $passUser
     * @param User $currentAgent
     * @return void
     */
    public function distributeAgentCommission(User $passUser, User $currentAgent)
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
        $commissionRecord = $passUser->commissionsAsPassenger()->first(); // Get the first commission record for the passenger

        if (!$commissionRecord) {
            // If it's the first time booking, assign the current agent as the first agent
            Commission::create([
                'agent_id' => $currentAgent->id,    // Current agent earns full commission
                'passenger_id' => $passUser->id,
                'amount' => $primaryCommission->amount,
                'is_first_time' => true,
                'first_agent_id' => $currentAgent->id, // The first agent is the current one
            ]);
        } else {
            // If it's a subsequent booking, split the commission
            $firstAgent = $commissionRecord->firstAgent; // The first agent who booked this passenger

            // Record commission for the first agent (half of the amount)
            Commission::create([
                'agent_id' => $firstAgent->id,
                'passenger_id' => $passUser->id,
                'amount' => $secondaryCommission->amount,
                'is_first_time' => false,
                'first_agent_id' => $firstAgent->id, // First agent remains the same
            ]);

            // Record commission for the current agent (half of the amount)
            Commission::create([
                'agent_id' => $currentAgent->id,
                'passenger_id' => $passUser->id,
                'amount' => $secondaryCommission->amount,
                'is_first_time' => false,
                'first_agent_id' => $firstAgent->id, // First agent remains the same
            ]);
        }
    }
}
