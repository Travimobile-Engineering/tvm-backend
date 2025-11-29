<?php

namespace App\Services\SMS;

use App\Contracts\SMS;
use InvalidArgumentException;

class SmsServiceFactory
{
    public static function make(string $provider): SMS
    {
        return match ($provider) {
            'africastalking' => new AfricasTalkingSmsService,
            'termii' => new TermiiSmsService,
            default => throw new InvalidArgumentException("Unsupported SMS provider: $provider"),
        };
    }
}
