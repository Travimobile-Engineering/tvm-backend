<?php

namespace App\DTO;

use App\Http\Requests\AgentBookingRequest;
use App\Models\Trip;
use App\Models\User;

class CreateBookingLogPaymentData
{
    public function __construct(
        public readonly AgentBookingRequest $request,
        public readonly User $passenger,
        public readonly User $user,
        public readonly float $amountPaid,
        public readonly Trip $trip,
        public readonly float $chargeAmount,
    ) {}
}
