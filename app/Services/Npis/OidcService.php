<?php

namespace App\Services\Npis;

use App\Exceptions\NpisApiException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

class OidcService
{
    /**
     * @return array<string, mixed>
     */
    public function verify(string $idToken): array
    {
        $parts = explode('.', $idToken);

        if (count($parts) !== 3) {
            throw NpisApiException::make(401, 'UNAUTHENTICATED', 'The organizer identity token is not valid.');
        }

        $header = json_decode($this->base64UrlDecode($parts[0]), true);

        if (! is_array($header) || empty($header['alg'])) {
            throw NpisApiException::make(401, 'UNAUTHENTICATED', 'The organizer identity token is not valid.');
        }

        $algorithm = (string) $header['alg'];
        $allowed = (array) config('npis.oidc.allowed_algorithms', []);

        if (! in_array($algorithm, $allowed, true)) {
            throw NpisApiException::make(401, 'UNAUTHENTICATED', 'The organizer identity token uses an unsupported algorithm.');
        }

        try {
            if ($algorithm === 'HS256') {
                $secret = (string) config('npis.oidc.dev_secret');
                if ($secret === '' || (app()->isProduction() && ! config('npis.oidc.allow_hs256', false))) {
                    throw NpisApiException::make(401, 'UNAUTHENTICATED', 'The organizer identity token is not valid.');
                }
                $claims = (array) JWT::decode($idToken, new Key($secret, 'HS256'));
            } else {
                $key = $this->publicKeyFor((string) ($header['kid'] ?? ''));
                $claims = (array) JWT::decode($idToken, new Key($key, $algorithm));
            }
        } catch (NpisApiException $exception) {
            throw $exception;
        } catch (Throwable) {
            throw NpisApiException::make(401, 'UNAUTHENTICATED', 'The organizer identity token is not valid or has expired.');
        }

        $issuer = config('npis.oidc.issuer');
        if ($issuer && ($claims['iss'] ?? null) !== $issuer) {
            throw NpisApiException::make(401, 'UNAUTHENTICATED', 'The organizer identity token was issued by an untrusted provider.');
        }

        $audience = config('npis.oidc.audience');
        if ($audience) {
            $audiences = (array) ($claims['aud'] ?? []);
            if (! in_array($audience, $audiences, true)) {
                throw NpisApiException::make(401, 'UNAUTHENTICATED', 'The organizer identity token is not intended for this application.');
            }
        }

        return $claims;
    }

    private function publicKeyFor(string $kid): string
    {
        $jwksUrl = (string) config('npis.oidc.jwks_url');

        if ($jwksUrl === '') {
            throw NpisApiException::make(503, 'OIDC_NOT_CONFIGURED', 'Organizer identity is not configured.');
        }

        $jwks = Cache::remember('npis:oidc:jwks', now()->addMinutes(5), function () use ($jwksUrl): array {
            $response = Http::timeout(5)->get($jwksUrl);

            if (! $response->successful()) {
                throw NpisApiException::make(503, 'OIDC_UNAVAILABLE', 'Could not reach the organizer identity provider.');
            }

            return (array) $response->json('keys', []);
        });

        foreach ($jwks as $key) {
            if ($kid !== '' && ($key['kid'] ?? null) !== $kid) {
                continue;
            }

            if (! isset($key['n'], $key['e'])) {
                continue;
            }

            return $this->rsaPublicKeyFromJwk($key);
        }

        throw NpisApiException::make(401, 'UNAUTHENTICATED', 'The organizer identity token signing key is unknown.');
    }

    /**
     * @param  array<string, string>  $jwk
     */
    private function rsaPublicKeyFromJwk(array $jwk): string
    {
        $modulus = $this->base64UrlDecode((string) $jwk['n']);
        $exponent = $this->base64UrlDecode((string) $jwk['e']);

        $rsaPublicKey = $this->asn1Sequence(
            $this->asn1Integer($modulus).
            $this->asn1Integer($exponent)
        );

        $bitString = "\x03".$this->asn1Length(strlen($rsaPublicKey) + 1)."\x00".$rsaPublicKey;
        $algorithm = $this->asn1Sequence(
            "\x06\x09\x2a\x86\x48\x86\xf7\x0d\x01\x01\x01". // rsaEncryption
            "\x05\x00"
        );

        $spki = $this->asn1Sequence($algorithm.$bitString);

        return "-----BEGIN PUBLIC KEY-----\n".chunk_split(base64_encode($spki), 64, "\n")."-----END PUBLIC KEY-----\n";
    }

    private function asn1Length(int $length): string
    {
        if ($length < 0x80) {
            return chr($length);
        }

        $binary = ltrim(pack('N', $length), "\x00");

        return chr(0x80 | strlen($binary)).$binary;
    }

    private function asn1Integer(string $value): string
    {
        $value = ltrim($value, "\x00");

        if ($value === '' || ord($value[0]) > 0x7F) {
            $value = "\x00".$value;
        }

        return "\x02".$this->asn1Length(strlen($value)).$value;
    }

    private function asn1Sequence(string $value): string
    {
        return "\x30".$this->asn1Length(strlen($value)).$value;
    }

    private function base64UrlDecode(string $value): string
    {
        $remainder = strlen($value) % 4;

        if ($remainder) {
            $value .= str_repeat('=', 4 - $remainder);
        }

        $decoded = base64_decode(strtr($value, '-_', '+/'), true);

        if ($decoded === false) {
            throw NpisApiException::make(401, 'UNAUTHENTICATED', 'The organizer identity token is not valid.');
        }

        return $decoded;
    }
}
