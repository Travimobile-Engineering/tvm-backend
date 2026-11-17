<?php

namespace App\Enum\Npis;

enum PostSummitPlan: string
{
    case LeaveImmediately = 'leave_immediately';
    case ExtendStay = 'extend_stay';
    case Undecided = 'undecided';

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::cases());
    }
}
