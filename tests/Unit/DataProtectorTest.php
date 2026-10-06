<?php

namespace Tests\Unit;

use App\Services\DataProtection\DataProtector;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class DataProtectorTest extends TestCase
{
    private function key(string $seed): string
    {
        return 'base64:'.base64_encode(str_repeat($seed, 32));
    }

    private function protector(array $previous = []): DataProtector
    {
        return new DataProtector($this->key('k'), $previous);
    }

    public function test_it_encrypts_and_decrypts_a_value(): void
    {
        $protector = $this->protector();

        $encrypted = $protector->encrypt('secret-value');

        $this->assertIsString($encrypted);
        $this->assertStringStartsWith(DataProtector::PREFIX, $encrypted);
        $this->assertNotSame('secret-value', $encrypted);
        $this->assertSame('secret-value', $protector->decrypt($encrypted));
    }

    public function test_it_preserves_null_values(): void
    {
        $protector = $this->protector();

        $this->assertNull($protector->encrypt(null));
        $this->assertNull($protector->decrypt(null));
    }

    public function test_it_returns_legacy_plaintext_untouched(): void
    {
        $protector = $this->protector();

        $this->assertSame('plain-legacy', $protector->decrypt('plain-legacy'));
        $this->assertFalse($protector->isEncrypted('plain-legacy'));
    }

    public function test_it_recognises_encrypted_payloads(): void
    {
        $protector = $this->protector();

        $this->assertTrue($protector->isEncrypted($protector->encrypt('secret')));
        $this->assertFalse($protector->isEncrypted(null));
        $this->assertFalse($protector->isEncrypted(12345));
    }

    public function test_it_produces_a_different_payload_for_the_same_plaintext(): void
    {
        $protector = $this->protector();

        $this->assertNotSame($protector->encrypt('same'), $protector->encrypt('same'));
    }

    public function test_it_does_not_double_encrypt(): void
    {
        $protector = $this->protector();

        $encrypted = $protector->encrypt('secret');

        $this->assertSame($encrypted, $protector->encrypt($encrypted));
    }

    public function test_it_fails_to_decrypt_a_tampered_payload(): void
    {
        $protector = $this->protector();

        $payload = $protector->encrypt('secret');
        $body = (string) base64_decode(substr($payload, strlen(DataProtector::PREFIX)), true);
        $body[20] = $body[20] ^ "\x01";
        $tampered = DataProtector::PREFIX.base64_encode($body);

        $this->expectException(RuntimeException::class);

        $protector->decrypt($tampered);
    }

    public function test_it_decrypts_data_encrypted_with_a_previous_key(): void
    {
        $old = new DataProtector($this->key('a'));
        $payload = $old->encrypt('rotate-me');

        $rotated = new DataProtector($this->key('b'), [$this->key('a')]);

        $this->assertSame('rotate-me', $rotated->decrypt($payload));
    }

    public function test_it_rejects_an_empty_key(): void
    {
        $this->expectException(RuntimeException::class);

        new DataProtector('');
    }

    public function test_it_builds_a_deterministic_normalized_blind_index(): void
    {
        $protector = $this->protector();

        $index = $protector->blindIndex('Ada@Example.com');

        $this->assertIsString($index);
        $this->assertSame(64, strlen($index));
        $this->assertSame($index, $protector->blindIndex('  ada@example.com  '));
        $this->assertNotSame($index, $protector->blindIndex('other@example.com'));
        $this->assertNull($protector->blindIndex(null));
    }

    public function test_it_builds_a_case_sensitive_blind_index_when_normalization_is_disabled(): void
    {
        $protector = $this->protector();

        $index = $protector->blindIndex('TvM_AbC123', false);

        $this->assertSame($index, $protector->blindIndex('TvM_AbC123', false));
        $this->assertNotSame($index, $protector->blindIndex('tvm_abc123', false));
    }

    public function test_it_uses_a_dedicated_index_key_when_provided(): void
    {
        $withDefault = new DataProtector($this->key('k'));
        $withDedicated = new DataProtector($this->key('k'), [], $this->key('i'));

        $this->assertNotSame(
            $withDefault->blindIndex('ada@example.com'),
            $withDedicated->blindIndex('ada@example.com')
        );
    }
}
