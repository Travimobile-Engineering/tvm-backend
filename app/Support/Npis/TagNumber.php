<?php

namespace App\Support\Npis;

class TagNumber
{
    public static function charset(): string
    {
        return (string) config('npis.tag.charset');
    }

    public static function checkCharacter(string $body): string
    {
        $charset = self::charset();
        $sum = 0;

        foreach (str_split($body) as $position => $character) {
            $sum += strpos($charset, $character) * ($position + 1);
        }

        return $charset[$sum % strlen($charset)];
    }

    public static function generate(bool $vendor = false): string
    {
        $charset = self::charset();
        $length = (int) config('npis.tag.random_length', 4);
        $random = '';

        for ($i = 0; $i < $length; $i++) {
            $random .= $charset[random_int(0, strlen($charset) - 1)];
        }

        $body = $vendor ? 'V'.$random : $random;
        $check = self::checkCharacter($body);

        return config('npis.tag.prefix').($vendor ? '-V-' : '-').$random.$check;
    }

    public static function validate(?string $value): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        $normalized = preg_replace('/[^A-Z0-9]/', '', strtoupper($value));
        $prefix = strtoupper((string) config('npis.tag.prefix'));

        if (! str_starts_with($normalized, $prefix)) {
            return null;
        }

        $remainder = substr($normalized, strlen($prefix));
        $charset = self::charset();

        if (strlen($remainder) === 6 && $remainder[0] === 'V') {
            $candidate = substr($remainder, 1);
            $random = substr($candidate, 0, 4);

            if (self::containsInvalidCharacter($candidate) || self::containsInvalidCharacter($random)) {
                return null;
            }

            return self::checkCharacter('V'.$random) === $candidate[4]
                ? $prefix.'-V-'.$candidate
                : null;
        }

        if (strlen($remainder) !== 5 || self::containsInvalidCharacter($remainder)) {
            return null;
        }

        if (self::checkCharacter(substr($remainder, 0, 4)) !== $remainder[4]) {
            return null;
        }

        return $prefix.'-'.$remainder;
    }

    private static function containsInvalidCharacter(string $value): bool
    {
        foreach (str_split($value) as $character) {
            if (! str_contains(self::charset(), $character)) {
                return true;
            }
        }

        return false;
    }

    public static function unique(callable $exists, bool $vendor = false): string
    {
        for ($attempt = 0; $attempt < 50; $attempt++) {
            $candidate = self::generate($vendor);

            if (! $exists($candidate)) {
                return $candidate;
            }
        }

        throw new \RuntimeException('Unable to generate a unique Summit Tag Number.');
    }
}
