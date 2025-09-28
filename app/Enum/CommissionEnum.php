<?php

namespace App\Enum;

enum CommissionEnum: int
{
    // Booking Commission
    case AGENT = 67;
    case COMPANY = 33;

    /**
     * Get the database slug for this commission type
     */
    public function slug(): string
    {
        return match ($this) {
            self::AGENT => 'agent',
            self::COMPANY => 'company',
        };
    }
}
