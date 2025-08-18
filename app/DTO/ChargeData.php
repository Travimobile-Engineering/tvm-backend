<?php

namespace App\DTO;

class ChargeData
{
    public function __construct(
        public $user,
        public $wallet,
        public array $charges,
        public float $amount,
        public string $title,
        public string $referencePrefix,
        public string $source = 'wallet',
        public string $chargeFrom = 'balance',
        public ?string $message = null,
    ) {}
}
