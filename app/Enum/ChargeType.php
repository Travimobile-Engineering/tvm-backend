<?php

namespace App\Enum;

enum ChargeType: string
{
    case ADMIN = "Admin Charges";
    case SMS = "SMS";
    case VAT = "VAT";
    case INSURANCE = "Insurance";
    case UNION = "Union";
}
