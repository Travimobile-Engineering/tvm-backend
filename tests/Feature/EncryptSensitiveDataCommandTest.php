<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\User;
use App\Models\UserBank;
use App\Services\DataProtection\DataProtector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EncryptSensitiveDataCommandTest extends TestCase
{
    use RefreshDatabase;

    private function protector(): DataProtector
    {
        return app(DataProtector::class);
    }

    private function seedLegacyUser(): int
    {
        return DB::table('users')->insertGetId([
            'first_name' => 'Legacy',
            'last_name' => 'User',
            'email' => 'legacy@example.com',
            'phone_number' => '08012345678',
            'address' => '1 Old Street',
            'next_of_kin_full_name' => 'Next Kin',
            'next_of_kin_phone_number' => '08099998888',
            'verification_code' => '12345',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_it_encrypts_existing_plaintext_data_and_populates_blind_indexes(): void
    {
        $id = $this->seedLegacyUser();

        $this->artisan('data-protection:encrypt')->assertSuccessful();

        $protector = $this->protector();
        $raw = DB::table('users')->where('id', $id)->first();

        $this->assertTrue($protector->isEncrypted($raw->email));
        $this->assertTrue($protector->isEncrypted($raw->phone_number));
        $this->assertTrue($protector->isEncrypted($raw->address));
        $this->assertTrue($protector->isEncrypted($raw->next_of_kin_full_name));
        $this->assertTrue($protector->isEncrypted($raw->next_of_kin_phone_number));

        $this->assertSame($protector->blindIndex('legacy@example.com'), $raw->email_hash);
        $this->assertSame($protector->blindIndex('08012345678'), $raw->phone_number_hash);

        // Lookups and reads keep working against the migrated row.
        $this->assertSame($id, User::byEmail('legacy@example.com')->firstOrFail()->id);
        $this->assertSame('legacy@example.com', User::find($id)->email);
    }

    public function test_it_is_idempotent(): void
    {
        $id = $this->seedLegacyUser();

        $this->artisan('data-protection:encrypt')->assertSuccessful();

        $first = DB::table('users')->where('id', $id)->first();

        $this->artisan('data-protection:encrypt')->assertSuccessful();

        $second = DB::table('users')->where('id', $id)->first();

        $this->assertSame($first->email, $second->email);
        $this->assertSame($first->email_hash, $second->email_hash);
        $this->assertSame($first->address, $second->address);
    }

    public function test_it_does_not_modify_data_during_a_dry_run(): void
    {
        $id = $this->seedLegacyUser();

        $this->artisan('data-protection:encrypt', ['--dry-run' => true])->assertSuccessful();

        $raw = DB::table('users')->where('id', $id)->first();

        $this->assertSame('legacy@example.com', $raw->email);
        $this->assertSame('1 Old Street', $raw->address);
        $this->assertNull($raw->email_hash);
    }

    public function test_it_encrypts_phase_three_tables_and_populates_blind_indexes(): void
    {
        $user = User::factory()->create();

        $bankId = DB::table('user_banks')->insertGetId([
            'user_id' => $user->id,
            'bank_name' => 'Access Bank',
            'account_number' => '0123456789',
            'account_name' => 'Legacy Account',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $documentId = DB::table('documents')->insertGetId([
            'user_id' => $user->id,
            'type' => 'license',
            'number' => 'LIC-12345',
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->artisan('data-protection:encrypt')->assertSuccessful();

        $protector = $this->protector();

        $bank = DB::table('user_banks')->where('id', $bankId)->first();
        $document = DB::table('documents')->where('id', $documentId)->first();

        $this->assertTrue($protector->isEncrypted($bank->account_number));
        $this->assertTrue($protector->isEncrypted($bank->account_name));
        $this->assertSame($protector->blindIndex('0123456789'), $bank->account_number_hash);

        $this->assertTrue($protector->isEncrypted($document->number));
        $this->assertSame($protector->blindIndex('LIC-12345'), $document->number_hash);

        // Blind-index lookups and decrypted reads keep working.
        $this->assertSame($bankId, UserBank::byAccountNumber('0123456789')->firstOrFail()->id);
        $this->assertSame($documentId, Document::byNumber('LIC-12345')->firstOrFail()->id);
        $this->assertSame('0123456789', UserBank::find($bankId)->account_number);
        $this->assertSame('LIC-12345', Document::find($documentId)->number);
    }
}
