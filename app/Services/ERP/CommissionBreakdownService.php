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
        $companyPercent = $companyPercent ?? (100 - $agentPercent);

        if (abs(($agentPercent + $companyPercent) - 100) > 0.0001) {
            throw new \InvalidArgumentException("Agent and company percentages must add up to 100.");
        }

        $agentShare = round(($totalAmount * $agentPercent) / 100, 2);
        $companyShare = round(($totalAmount * $companyPercent) / 100, 2);

        return [
            'agent_share' => $agentShare,
            'company_share' => $companyShare,
        ];
    }
}
