<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\AccountTransfer;
use App\Models\AdminBulkTransfer;
use App\Models\Document;
use App\Models\User;
use App\Models\UserBank;
use App\Services\DataProtection\DataProtector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PhaseThreeEncryptionTest extends TestCase
{
    use RefreshDatabase;

    private function protector(): DataProtector
    {
        return app(DataProtector::class);
    }

    public function test_user_bank_details_are_encrypted_at_rest_with_a_blind_index(): void
    {
        $user = User::factory()->create();

        $bank = UserBank::create([
            'user_id' => $user->id,
            'bank_name' => 'Access Bank',
            'account_number' => '0123456789',
            'account_name' => 'Ada Lovelace',
        ]);

        $raw = DB::table('user_banks')->where('id', $bank->id)->first();

        $this->assertTrue($this->protector()->isEncrypted($raw->account_number));
        $this->assertTrue($this->protector()->isEncrypted($raw->account_name));
        $this->assertSame($this->protector()->blindIndex('0123456789'), $raw->account_number_hash);

        $fresh = $bank->fresh();
        $this->assertSame('0123456789', $fresh->account_number);
        $this->assertSame('Ada Lovelace', $fresh->account_name);
        $this->assertSame($bank->id, UserBank::byAccountNumber('0123456789')->firstOrFail()->id);
    }

    public function test_document_numbers_are_encrypted_at_rest_with_a_blind_index(): void
    {
        $user = User::factory()->create();

        $document = Document::create([
            'user_id' => $user->id,
            'type' => 'license',
            'number' => 'LIC-12345',
            'status' => 'pending',
        ]);

        $raw = DB::table('documents')->where('id', $document->id)->first();

        $this->assertTrue($this->protector()->isEncrypted($raw->number));
        $this->assertSame($this->protector()->blindIndex('LIC-12345'), $raw->number_hash);

        $this->assertSame('LIC-12345', $document->fresh()->number);
        $this->assertSame($document->id, Document::byNumber('LIC-12345')->firstOrFail()->id);
    }

    public function test_legacy_plaintext_bank_rows_remain_readable_and_searchable(): void
    {
        $user = User::factory()->create();

        $id = DB::table('user_banks')->insertGetId([
            'user_id' => $user->id,
            'bank_name' => 'Legacy Bank',
            'account_number' => '0999999999',
            'account_name' => 'Legacy Holder',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertSame('0999999999', UserBank::find($id)->account_number);
        $this->assertSame($id, UserBank::byAccountNumber('0999999999')->firstOrFail()->id);
    }

    public function test_admin_payment_accounts_are_encrypted_at_rest(): void
    {
        $account = Account::create([
            'bank_name' => 'Access Bank',
            'account_name' => 'Travicom Ltd',
            'account_number' => '0123456789',
        ]);

        $raw = DB::table('accounts')->where('id', $account->id)->first();

        $this->assertTrue($this->protector()->isEncrypted($raw->account_name));
        $this->assertTrue($this->protector()->isEncrypted($raw->account_number));

        $fresh = $account->fresh();
        $this->assertSame('Travicom Ltd', $fresh->account_name);
        $this->assertSame('0123456789', $fresh->account_number);
    }

    public function test_transfer_responses_are_encrypted_at_rest(): void
    {
        $account = Account::create([
            'bank_name' => 'Access Bank',
            'account_name' => 'Travicom Ltd',
            'account_number' => '0123456789',
        ]);

        $transfer = AccountTransfer::create([
            'account_id' => $account->id,
            'amount' => 1000,
            'status' => 'pending',
            'response' => ['status' => true, 'message' => 'queued'],
        ]);

        $bulk = AdminBulkTransfer::create([
            'reference' => 'ref-123',
            'total_amount' => 1000,
            'total_transfers' => 1,
            'status' => 'processing',
            'response' => ['status' => true],
        ]);

        $rawTransfer = DB::table('account_transfers')->where('id', $transfer->id)->first();
        $rawBulk = DB::table('admin_bulk_transfers')->where('id', $bulk->id)->first();

        $this->assertTrue($this->protector()->isEncrypted($rawTransfer->response));
        $this->assertTrue($this->protector()->isEncrypted($rawBulk->response));

        $this->assertSame(['status' => true, 'message' => 'queued'], $transfer->fresh()->response);
        $this->assertSame(['status' => true], $bulk->fresh()->response);
    }
}
