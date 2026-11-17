<?php

namespace App\Enum\Npis;

enum Sector: string
{
    case Police = 'police';
    case SecurityAgency = 'security_agency';
    case Government = 'government';
    case Transport = 'transport';
    case Hospitality = 'hospitality';
    case PrivateSector = 'private_sector';
    case Media = 'media';
    case Other = 'other';

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::cases());
    }
}
