<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class GenerateDataProtectionKeyTest extends TestCase
{
    public function test_it_generates_both_data_protection_keys(): void
    {
        $this->artisan('data-protection:key')
            ->expectsOutputToContain('DATA_PROTECTION_KEY=base64:')
            ->expectsOutputToContain('DATA_PROTECTION_INDEX_KEY=base64:')
            ->assertSuccessful();
    }

    public function test_the_generated_keys_are_distinct_valid_32_byte_keys(): void
    {
        Artisan::call('data-protection:key');

        $output = Artisan::output();

        preg_match_all('/^(DATA_PROTECTION_(?:INDEX_)?KEY)=(base64:.+)$/m', $output, $matches);

        $keys = array_combine($matches[1], $matches[2]);

        $this->assertArrayHasKey('DATA_PROTECTION_KEY', $keys);
        $this->assertArrayHasKey('DATA_PROTECTION_INDEX_KEY', $keys);

        foreach ($keys as $name => $value) {
            $decoded = base64_decode(substr($value, 7), true);
            $this->assertNotFalse($decoded, "{$name} is not valid base64");
            $this->assertSame(32, strlen((string) $decoded), "{$name} is not 32 bytes");
        }

        $this->assertNotSame($keys['DATA_PROTECTION_KEY'], $keys['DATA_PROTECTION_INDEX_KEY']);
    }
}
