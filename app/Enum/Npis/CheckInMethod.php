<?php

namespace App\Enum\Npis;

enum CheckInMethod: string
{
    case TagNumber = 'tag_number';
    case Qr = 'qr';

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::cases());
    }
}
