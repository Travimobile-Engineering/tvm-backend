<?php

namespace App\DTO;

class SendCodeData
{
    public function __construct(
        public readonly string $type,
        public readonly mixed $user,
        public readonly array $data,
        public readonly ?string $phone = null,
        public readonly ?string $message = null,
        public readonly ?string $subject = null,
        public readonly ?string $mailable = null,
    )
    {}
}
