<?php

namespace App\Enum;

enum ChargeType: string
{
    case ADMIN = "Admin";
    case SMS = "SMS";
    case VAT = "VAT";
    case INSURANCE = "Insurance";
    case UNION = "Union";
}
