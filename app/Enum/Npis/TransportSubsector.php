<?php

namespace App\Enum\Npis;

enum TransportSubsector: string
{
    case Road = 'road';
    case Air = 'air';
    case Sea = 'sea';
    case Rail = 'rail';

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::cases());
    }
}
