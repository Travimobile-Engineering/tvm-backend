<?php

namespace App\Enum\Npis;

enum PlanStatus: string
{
    case Undecided = 'undecided';
    case Preferred = 'preferred';
    case Confirmed = 'confirmed';

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::cases());
    }
}
