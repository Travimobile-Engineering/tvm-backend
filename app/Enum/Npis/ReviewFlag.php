<?php

namespace App\Enum\Npis;

enum ReviewFlag: string
{
    case PossibleDuplicate = 'possible_duplicate';
    case UnlistedUnit = 'unlisted_unit';

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::cases());
    }
}
