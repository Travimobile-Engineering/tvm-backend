<?php

namespace App\Enum\Npis;

enum VerificationPurpose: string
{
    case Registration = 'registration';
    case Reopen = 'reopen';

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::cases());
    }
}
