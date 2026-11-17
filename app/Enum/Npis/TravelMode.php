<?php

namespace App\Enum\Npis;

enum TravelMode: string
{
    case Air = 'air';
    case Road = 'road';
    case Rail = 'rail';
    case Sea = 'sea';
    case Undecided = 'undecided';

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::cases());
    }
}
