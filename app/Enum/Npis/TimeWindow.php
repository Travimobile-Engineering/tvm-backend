<?php

namespace App\Enum\Npis;

enum TimeWindow: string
{
    case EarlyMorning = 'early_morning';
    case Morning = 'morning';
    case Afternoon = 'afternoon';
    case Evening = 'evening';
    case Night = 'night';

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::cases());
    }
}
