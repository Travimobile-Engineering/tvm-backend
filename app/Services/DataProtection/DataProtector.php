<?php

declare(strict_types=1);

namespace App\Services\DataProtection;

use RuntimeException;

/**
 * Application owned encryption layer used to protect sensitive data at rest.
 *
 * Values are encrypted with AES-256-GCM (authenticated encryption) using a
 * randomly generated nonce per value. The resulting payload is stored with a
 * version prefix so encrypted values can be told apart from plaintext values
 * written before data protection was introduced. Reads are backwards
 * compatible: unknown/legacy plaintext values are returned as-is.
 */
class DataProtector
{
    public const PREFIX = 'enc:v1:';

    private const CIPHER = 'aes-256-gcm';

    private const IV_BYTES = 12;

    private const TAG_BYTES = 16;

    private const KEY_BYTES = 32;

    /**
     * Normalized 32 byte binary keys. The current (encryption) key is first,
     * followed by any previous keys used only for decryption.
     *
     * @var list<string>
     */
    private array $keys;

    /**
     * Key used to build deterministic blind indexes for searchable values.
     */
    private string $indexKey;

    /**
     * @param  list<string>  $previousKeys
     */
    public function __construct(string $key, array $previousKeys = [], ?string $indexKey = null)
    {
        if ($key === '') {
            throw new RuntimeException('Data protection key is not configured. Set DATA_PROTECTION_KEY.');
        }

        $this->keys = array_values(array_map(
            fn (string $candidate): string => $this->normalizeKey($candidate),
            array_merge([$key], $previousKeys)
        ));

        $this->indexKey = ($indexKey === null || $indexKey === '')
            ? $this->keys[0]
            : $this->normalizeKey($indexKey);
    }

    /**
     * Encrypt a plaintext value. Nulls are preserved and already encrypted
     * values are returned untouched so the operation is idempotent.
     */
    public function encrypt(?string $plaintext): ?string
    {
        if ($plaintext === null) {
            return null;
        }

        if ($this->isEncrypted($plaintext)) {
            return $plaintext;
        }

        $iv = random_bytes(self::IV_BYTES);
        $tag = '';

        $ciphertext = openssl_encrypt(
            $plaintext,
            self::CIPHER,
            $this->keys[0],
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            '',
            self::TAG_BYTES
        );

        if ($ciphertext === false) {
            throw new RuntimeException('Failed to encrypt value.');
        }

        return self::PREFIX.base64_encode($iv.$tag.$ciphertext);
    }

    /**
     * Decrypt an encrypted value. Values that are not encrypted (legacy
     * plaintext written before data protection) are returned as-is.
     */
    public function decrypt(?string $payload): ?string
    {
        if ($payload === null) {
            return null;
        }

        if (! $this->isEncrypted($payload)) {
            return $payload;
        }

        $decoded = base64_decode(substr($payload, strlen(self::PREFIX)), true);

        if ($decoded === false || strlen($decoded) < self::IV_BYTES + self::TAG_BYTES) {
            throw new RuntimeException('Encrypted payload is malformed.');
        }

        $iv = substr($decoded, 0, self::IV_BYTES);
        $tag = substr($decoded, self::IV_BYTES, self::TAG_BYTES);
        $ciphertext = substr($decoded, self::IV_BYTES + self::TAG_BYTES);

        foreach ($this->keys as $key) {
            $plaintext = openssl_decrypt(
                $ciphertext,
                self::CIPHER,
                $key,
                OPENSSL_RAW_DATA,
                $iv,
                $tag
            );

            if ($plaintext !== false) {
                return $plaintext;
            }
        }

        throw new RuntimeException('Unable to decrypt value with the configured data protection keys.');
    }

    /**
     * Determine whether a raw value was produced by this encryptor.
     */
    public function isEncrypted(mixed $value): bool
    {
        return is_string($value) && str_starts_with($value, self::PREFIX);
    }

    /**
     * Build a deterministic blind index (HMAC-SHA256, hex) for a searchable
     * value such as an email address or phone number.
     *
     * When $normalize is true the value is trimmed and lowercased so lookups
     * match regardless of input casing (suitable for emails). Case sensitive
     * values (e.g. API keys, referral codes) must pass false.
     */
    public function blindIndex(?string $value, bool $normalize = true): ?string
    {
        if ($value === null) {
            return null;
        }

        return hash_hmac('sha256', $normalize ? $this->normalize($value) : $value, $this->indexKey);
    }

    private function normalize(string $value): string
    {
        return mb_strtolower(trim($value));
    }

    /**
     * Accepts either a raw 32 byte key or a base64 encoded key (with or without
     * the "base64:" prefix). Anything else is hashed down to 32 bytes.
     */
    private function normalizeKey(string $key): string
    {
        if (str_starts_with($key, 'base64:')) {
            $key = (string) base64_decode(substr($key, 7), true);
        }

        if ($key === '') {
            throw new RuntimeException('Data protection key is invalid.');
        }

        return strlen($key) === self::KEY_BYTES ? $key : hash('sha256', $key, true);
    }
}
