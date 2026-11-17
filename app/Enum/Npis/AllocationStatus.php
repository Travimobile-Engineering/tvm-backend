<?php

namespace App\Enum\Npis;

enum AllocationStatus: string
{
    case NotRequested = 'not_requested';
    case Requested = 'requested';
    case Allocated = 'allocated';
    case Waitlisted = 'waitlisted';
    case Declined = 'declined';

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::cases());
    }
}
