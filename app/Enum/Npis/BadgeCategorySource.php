<?php

namespace App\Enum\Npis;

enum BadgeCategorySource: string
{
    case Derived = 'derived';
    case Assigned = 'assigned';

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::cases());
    }
}
