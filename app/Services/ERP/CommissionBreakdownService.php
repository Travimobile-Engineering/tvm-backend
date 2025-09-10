<?php

namespace App\Services\ERP;

class CommissionBreakdownService
{
    /**
     * Get the breakdown of a total commission amount.
     *
     * @param float|int $totalAmount
     * @param float|int $agentPercent
     * @param float|int|null $companyPercent Optional. If null, it will be 100 - agentPercent
     * @return array ['agent_share' => float, 'company_share' => float]
     */
    public function getBreakdown(float $totalAmount, float $agentPercent = 67, ?float $companyPercent = null): array
    {
        // Handle cases where company percent is explicitly provided
        if ($companyPercent !== null) {
            // Both are zero/negative
            if ($agentPercent <= 0 && $companyPercent <= 0) {
                return ['agent_share' => 0, 'company_share' => 0];
            }

            // Agent is zero/negative, company is positive
            if ($agentPercent <= 0 && $companyPercent > 0) {
                return [
                    'agent_share' => 0,
                    'company_share' => round(($totalAmount * $companyPercent) / 100, 2)
                ];
            }

            // Company is zero/negative, agent is positive
            if ($companyPercent <= 0 && $agentPercent > 0) {
                return [
                    'agent_share' => round(($totalAmount * $agentPercent) / 100, 2),
                    'company_share' => 0
                ];
            }

            // Both are positive - validate they add to 100
            if (abs(($agentPercent + $companyPercent) - 100) > 0.0001) {
                throw new \InvalidArgumentException("Agent and company percentages must add up to 100.");
            }

            return [
                'agent_share' => round(($totalAmount * $agentPercent) / 100, 2),
                'company_share' => round(($totalAmount * $companyPercent) / 100, 2)
            ];
        }

        // Agent is zero/negative - company gets everything
        if ($agentPercent <= 0) {
            return ['agent_share' => 0, 'company_share' => $totalAmount];
        }

        // Auto-calculate company percent and validate
        $companyPercent = 100 - $agentPercent;

        if ($companyPercent <= 0) {
            return ['agent_share' => $totalAmount, 'company_share' => 0];
        }

        // Both are positive and should add to 100 by calculation
        return [
            'agent_share' => round(($totalAmount * $agentPercent) / 100, 2),
            'company_share' => round(($totalAmount * $companyPercent) / 100, 2)
        ];
    }
}
