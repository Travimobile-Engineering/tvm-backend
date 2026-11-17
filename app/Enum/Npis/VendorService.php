<?php

namespace App\Enum\Npis;

enum VendorService: string
{
    case Usher = 'usher';
    case Caterer = 'caterer';
    case Cleaner = 'cleaner';
    case Security = 'security';
    case Photographer = 'photographer';
    case Videographer = 'videographer';
    case Journalist = 'journalist';
    case Driver = 'driver';
    case Decorator = 'decorator';
    case Other = 'other';

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::cases());
    }
}
