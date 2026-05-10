<?php

namespace App\DTO;

use App\Models\Trip;
use App\Models\User;
use Illuminate\Http\Request;

class CreateBookingLogPaymentData
{
    public function __construct(
        public readonly Request $request,
        public readonly User $passenger,
        public readonly User $user,
        public readonly float $amountPaid,
        public readonly Trip $trip,
        public readonly float $chargeAmount,
    ) {}
}
