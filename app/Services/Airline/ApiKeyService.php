<?php

namespace App\Services\Airline;

use App\Models\Airline;
use App\Models\AirlineApiKey;
use App\Models\AirlineAuditLog;
use App\Trait\HttpResponse;

class ApiKeyService
{
    use HttpResponse;

    public function generate(Airline $airline, string $environment, string $name = 'Default Key'): array
    {
        $prefix = $environment === 'production' ? 'live' : 'test';
        $publicKey = $this->buildPublicKey($prefix);
        $rawSecret = $this->buildSecretKey($prefix);

        $key = AirlineApiKey::create([
            'airline_id' => $airline->id,
            'name' => $name,
            'environment' => $environment,
            'public_key' => $publicKey,
            'secret_key_hash' => password_hash($rawSecret, PASSWORD_BCRYPT, ['cost' => 12]),
            'secret_key_prefix' => "sk_{$prefix}_",
            'secret_key_hint' => substr($rawSecret, -6),
            'is_active' => true,
        ]);

        $this->audit($airline, AirlineAuditLog::EVENT_KEY_GENERATED, [
            'key_id' => $key->id,
            'environment' => $environment,
            'name' => $name,
        ]);

        return [
            'key' => $key,
            'raw_secret' => $rawSecret,
        ];
    }

    /**
     * Rotate a key: revoke the old one and issue fresh credentials.
     *
     * @return array{key: AirlineApiKey, raw_secret: string}
     */
    public function rotate(AirlineApiKey $oldKey): array
    {
        $oldKey->update(['is_active' => false]);

        $this->audit($oldKey->airline, AirlineAuditLog::EVENT_KEY_ROTATED, [
            'old_key_id' => $oldKey->id,
            'environment' => $oldKey->environment,
        ]);

        return $this->generate($oldKey->airline, $oldKey->environment, "$oldKey->name (rotated)");
    }

    public function revoke(AirlineApiKey $key): void
    {
        $key->update(['is_active' => false]);
        $key->delete();

        $this->audit($key->airline, AirlineAuditLog::EVENT_KEY_REVOKED, [
            'key_id' => $key->id,
            'environment' => $key->environment,
        ]);
    }

    /**
     * Resolve and authenticate an incoming API request by public + secret key.
     * Returns the key model on success, null on failure.
     */
    public function authenticate(string $publicKey, string $rawSecret): ?AirlineApiKey
    {
        $key = AirlineApiKey::with('airline')
            ->active()
            ->where('public_key', $publicKey)
            ->first();

        if (! $key) {
            return null;
        }

        if (! $key->airline || ! $key->airline->is_active) {
            return null;
        }

        if (! $key->verifySecret($rawSecret)) {
            return null;
        }

        return $key;
    }

    private function buildPublicKey(string $prefix): string
    {
        return "pk_{$prefix}_".bin2hex(random_bytes(16));
    }

    private function buildSecretKey(string $prefix): string
    {
        return "sk_{$prefix}_".bin2hex(random_bytes(24));
    }

    private function audit(Airline $airline, string $event, array $meta = []): void
    {
        AirlineAuditLog::create([
            'airline_id' => $airline->id,
            'event' => $event,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'meta' => $meta,
        ]);
    }
}
