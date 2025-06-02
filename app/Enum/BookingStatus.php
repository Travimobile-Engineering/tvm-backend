<?php

namespace App\Enum;

enum BookingStatus: string
{
    case REQUEST = "request";
    case COMPLETED = "completed";
    case CANCELLED = "cancelled";
    case UPCOMING = "upcoming";
    case INPROGRESS = "inprogress";

    public static function isValid(string $value): bool
    {
        return in_array($value, self::values());
    }

    public static function values(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }
}
