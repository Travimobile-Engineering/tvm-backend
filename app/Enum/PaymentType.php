<?php

namespace App\Enum;

enum PaymentType: string
{
    const FUND_WALLET = "fund-wallet";
    const TRIP_BOOKING = "trip_booking";
    const PREMIUM_HIRE = "premium_hire";

    const CR = "CR";
    const DR = "DR";
}
