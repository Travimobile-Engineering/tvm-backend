<?php

namespace App\Enum;

enum ChargeType: string
{
    case ADMIN = 'Admin Charges';
    case SMS = 'SMS';
    case VAT = 'VAT';
    case INSURANCE = 'Insurance';
    case UNION = 'Union Remittance';
    case WITHDRAW_FEE = 'Withdraw fee';
    case MANIFEST = 'Manifest';
    case DRIVER_CHARGE = 'Driver charge';
    case AGENT_COMMISSION = 'Agent commission';
}
