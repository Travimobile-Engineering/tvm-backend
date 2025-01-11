<?php

namespace App\Enum;

enum PaymentMethod: string
{
    const WALLET = "wallet";
    const PAYSTACK = "paystack";
    const TRANSFER = "transfer";
}
