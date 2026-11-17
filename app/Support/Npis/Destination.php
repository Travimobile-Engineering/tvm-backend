<?php

namespace App\Support\Npis;

use App\Services\DataProtection\DataProtector;

class Destination
{
    public static function normalize(string $channel, string $destination): string
    {
        $destination = trim($destination);

        return $channel === 'email' ? strtolower($destination) : $destination;
    }

    public static function hash(string $destination): string
    {
        return app(DataProtector::class)->blindIndex($destination);
    }

    public static function mask(string $channel, string $destination): string
    {
        if ($channel === 'sms') {
            return substr($destination, 0, 4).' '.substr($destination, 4, 3).' *** '.substr($destination, -4);
        }

        [$local, $domain] = array_pad(explode('@', $destination, 2), 2, '');

        return substr($local, 0, 2).str_repeat('*', max(2, strlen($local) - 2)).'@'.$domain;
    }
}
