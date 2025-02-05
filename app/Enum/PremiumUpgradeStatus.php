<?php

namespace App\Enum;

enum PremiumUpgradeStatus: string
{
    const APPROVED = "approved";
    const PENDING = "pending";
    const DENIED = "denied";
}
