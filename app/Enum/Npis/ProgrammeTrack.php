<?php

namespace App\Enum\Npis;

enum ProgrammeTrack: string
{
    case Transportation = 'transportation';
    case Hospitality = 'hospitality';
    case Both = 'both';

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::cases());
    }
}
