<?php

namespace App\DTO;

class SendCodeData
{
    public function __construct(
        public readonly string $type,
        public readonly mixed $user,
        public readonly array $data,
        public readonly string $phone,
        public readonly string $message,
        public readonly string $subject,
        public readonly string $mailable,
    )
    {}
}
