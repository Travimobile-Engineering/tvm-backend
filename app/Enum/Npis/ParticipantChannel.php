<?php

namespace App\Enum\Npis;

enum ParticipantChannel: string
{
    case Sms = 'sms';
    case Email = 'email';

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::cases());
    }
}
