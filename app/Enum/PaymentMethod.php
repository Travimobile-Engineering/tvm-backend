<?php

namespace App\Enum;

enum PaymentMethod: string
{
    const DRIVERWALLET = 'driver_wallet';

    const WALLET = 'wallet';

    const PAYSTACK = 'paystack';

    const TRANSFER = 'transfer';
}
