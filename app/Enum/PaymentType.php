<?php

namespace App\Enum;

enum PaymentType: string
{
    const FUND_WALLET = "fund-wallet";
    const TRIP_BOOKING = "trip_booking";

    const CR = "CR";
    const DR = "DR";
}
