<?php

namespace App\Enum\Npis;

enum CertificateEligibility: string
{
    case NotEligible = 'not_eligible';
    case Eligible = 'eligible';
    case Issued = 'issued';

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::cases());
    }
}
