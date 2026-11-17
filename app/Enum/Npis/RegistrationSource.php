<?php

namespace App\Enum\Npis;

enum RegistrationSource: string
{
    case Online = 'online';
    case LegacyImport = 'legacy_import';

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::cases());
    }
}
