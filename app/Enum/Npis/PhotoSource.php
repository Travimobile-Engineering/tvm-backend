<?php

namespace App\Enum\Npis;

enum PhotoSource: string
{
    case Registrant = 'registrant';
    case Desk = 'desk';

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::cases());
    }
}
