<?php

namespace App\Enum\Npis;

enum OrganizerRole: string
{
    case SuperAdmin = 'super_admin';
    case Registration = 'registration';
    case Logistics = 'logistics';
    case Training = 'training';
    case Checkin = 'checkin';

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::cases());
    }
}
