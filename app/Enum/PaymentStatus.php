<?php

namespace App\Enum;

enum PaymentStatus: string
{
    case PENDING = 'pending';
    case PAID = 'paid';
}
