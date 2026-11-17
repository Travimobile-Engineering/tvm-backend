<?php

namespace App\Enum\Npis;

enum AccommodationPreference: string
{
    case OrganizerArranged = 'organizer_arranged';
    case SelfArranged = 'self_arranged';

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::cases());
    }
}
