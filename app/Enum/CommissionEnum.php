<?php

namespace App\Enum;

enum CommissionEnum: int
{
    // Booking Commission
    case BOOKING_AGENT_PERCENT = 67;
    case BOOKING_COMPANY_PERCENT = 33;

    // ERP Commission
    case ERP_AGENT_PERCENT = 60;
    case ERP_COMPANY_PERCENT = 40;
}
