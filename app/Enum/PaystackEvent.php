<?php

namespace App\Enum;

enum PaystackEvent: string
{
    const CHARGE_SUCCESS = 'charge.success';
    const CHARGE_FAILURE = 'charge.failed';
    const CHARGE_REFUNDED = 'charge.refunded';
    const CHARGE_PENDING = 'charge.pending';

    // Transfer
    const TRANSFER_SUCCESS = 'transfer.success';
    const TRANSFER_FAILED = 'transfer.failed';
    const TRANSFER_REVERSED = 'transfer.reversed';
}
