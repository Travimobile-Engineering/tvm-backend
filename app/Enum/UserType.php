<?php

namespace App\Enum;

enum UserType: string
{
    case DRIVER = 'driver';
    case AGENT = 'agent';
    case PASSENGER = 'passenger';
    case SECURITY = 'security';
    case FOO = 'foo';
    case AFOO = 'afoo';
    case AIRLINE = 'airline';

    /**
     * Return only specific user type group values
     */
    public static function group(array $cases): array
    {
        return array_map(fn (self $case) => $case->value, $cases);
    }

    /**
     * Group of all regular app users
     */
    public static function appUsers(): array
    {
        return [
            self::DRIVER,
            self::AGENT,
            self::PASSENGER,
            self::FOO,
            self::AFOO,
            self::AIRLINE,
        ];
    }

    /**
     * Group for all security agency roles
     */
    public static function agencyUsers(): array
    {
        return [
            self::SECURITY,
            self::AIRLINE,
        ];
    }
}
