<?php

namespace App\Enum;

enum PaymentType: string
{
    const FUND_WALLET = "fund-wallet";

    const CR = "CR";
    const DR = "DR";
}
