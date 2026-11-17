<?php

namespace App\Enum\Npis;

enum ParticipantType: string
{
    case Delegate = 'delegate';
    case Vendor = 'vendor';

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::cases());
    }
}
