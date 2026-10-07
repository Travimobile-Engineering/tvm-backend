<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\DataProtection\DataProtector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UserEncryptionTest extends TestCase
{
    use RefreshDatabase;

    private function protector(): DataProtector
    {
        return app(DataProtector::class);
    }

    public function test_email_and_phone_are_encrypted_at_rest_with_blind_indexes(): void
    {
        $user = User::factory()->create([
            'email' => 'Ada@Example.com',
            'phone_number' => '08012345678',
        ]);

        $raw = DB::table('users')->where('id', $user->id)->first();

        $this->assertTrue($this->protector()->isEncrypted($raw->email));
        $this->assertTrue($this->protector()->isEncrypted($raw->phone_number));
        $this->assertSame($this->protector()->blindIndex('Ada@Example.com'), $raw->email_hash);
        $this->assertSame($this->protector()->blindIndex('08012345678'), $raw->phone_number_hash);

        // Reads round-trip back to plaintext.
        $this->assertSame('Ada@Example.com', $user->fresh()->email);
        $this->assertSame('08012345678', $user->fresh()->phone_number);
    }

    public function test_users_can_be_found_by_email_and_phone(): void
    {
        $user = User::factory()->create([
            'email' => 'lookup@example.com',
            'phone_number' => '08087654321',
        ]);

        $this->assertSame($user->id, User::byEmail('lookup@example.com')->firstOrFail()->id);
        // Normalized lookup (different case / whitespace) still matches.
        $this->assertSame($user->id, User::byEmail('  LOOKUP@example.com  ')->firstOrFail()->id);
        $this->assertSame($user->id, User::byPhone('08087654321')->firstOrFail()->id);
    }

    public function test_legacy_plaintext_rows_remain_readable_and_searchable(): void
    {
        $id = DB::table('users')->insertGetId([
            'first_name' => 'Legacy',
            'last_name' => 'User',
            'email' => 'legacy@example.com',
            'phone_number' => '08000000000',
            'verification_code' => '12345',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertSame('legacy@example.com', User::find($id)->email);
        $this->assertSame($id, User::byEmail('legacy@example.com')->firstOrFail()->id);
        $this->assertSame($id, User::byPhone('08000000000')->firstOrFail()->id);
    }

    public function test_other_sensitive_attributes_are_encrypted_at_rest(): void
    {
        $user = User::factory()->create([
            'address' => '12 Marina Road, Lagos',
            'next_of_kin_full_name' => 'John Doe',
            'next_of_kin_phone_number' => '08011112222',
        ]);

        $raw = DB::table('users')->where('id', $user->id)->first();

        foreach (['address', 'next_of_kin_full_name', 'next_of_kin_phone_number'] as $column) {
            $this->assertTrue($this->protector()->isEncrypted($raw->{$column}), "{$column} is not encrypted");
        }

        $fresh = $user->fresh();
        $this->assertSame('12 Marina Road, Lagos', $fresh->address);
        $this->assertSame('John Doe', $fresh->next_of_kin_full_name);
        $this->assertSame('08011112222', $fresh->next_of_kin_phone_number);
    }
}
