<?php

namespace App\Services\Admin;

use App\Models\Fee;
use App\Models\Bank;
use App\Models\Account;
use App\Models\AdminBulkTransfer;
use App\Enum\AccountTransferStatus;
use App\Services\Curl\PostCurlService;

class AccountService
{
    public function initiateTransfer(array $typesAndAmounts)
    {
        foreach ($typesAndAmounts as $type => $amount) {
            $account = $this->findAccountByFeeType($type);

            if (!$account) {
                logger()->warning("No account found for fee type: {$type}, skipping transfer.");
                continue;
            }

            if (!empty($account->recipient_code)) {
                $this->transferToAccount($account, $amount);
                continue;
            }

            $bank = $this->findBankByAccount($account);
            $data = $this->createPaystackRecipient($account, $bank);

            $account->update([
                'recipient_code' => $data['recipient_code'],
            ]);

            $this->transferToAccount($account, $amount);
        }
    }

    public function transferToAccount($account, $amount)
    {
        $account->accountTransfers()->create([
            'amount' => $amount,
            'status' => 'pending'
        ]);

        logger()->info('Transfer initiated successfully for processing');
    }

    public function accumulateTransfersByAccount()
    {
        $accumulatedTransfers = [];

        // Get all pending transfers grouped by account
        AccountTransfer::with('account')
            ->where('status', AccountTransferStatus::PENDING->value)
            ->whereNull('bulk_transfer_id')
            ->chunkById(100, function ($transfers) use (&$accumulatedTransfers) {
                foreach ($transfers as $transfer) {
                    $accountId = $transfer->account_id;

                    if (!isset($accumulatedTransfers[$accountId])) {
                        $accumulatedTransfers[$accountId] = [
                            'account' => $transfer->account,
                            'total_amount' => 0,
                            'transfers' => [],
                            'recipient_code' => $transfer->account->recipient_code
                        ];
                    }

                    $accumulatedTransfers[$accountId]['total_amount'] += $transfer->amount;
                    $accumulatedTransfers[$accountId]['transfers'][] = $transfer;
                }
            });

        return $accumulatedTransfers;
    }

    public function createBulkTransferForAccount($transfers, $totalAmount)
    {
        return DB::transaction(function () use ($transfers, $totalAmount) {
            // Create bulk transfer record
            $bulkTransfer = AdminBulkTransfer::create([
                'reference' => (string) Str::uuid(),
                'total_amount' => $totalAmount,
                'total_transfers' => count($transfers),
                'status' => AccountTransferStatus::PROCESSING->value,
            ]);

            // Update individual transfers with bulk transfer ID
            $transferIds = collect($transfers)->pluck('id');

            AccountTransfer::whereIn('id', $transferIds)
                ->update([
                    'bulk_transfer_id' => $bulkTransfer->id,
                    'status' => AccountTransferStatus::PROCESSING->value,
                ]);

            return $bulkTransfer;
        });
    }

    private function findAccountByFeeType($type): ?Account
    {
        $feeIds = Fee::where('name', $type)->pluck('id');

        return Account::where(function ($query) use ($feeIds) {
            foreach ($feeIds as $id) {
                $query->orWhereJsonContains('fees', $id);
            }
        })->first();
    }

    private function findBankByAccount(Account $account): Bank
    {
        return Bank::where('name', $account->bank_name)->firstOrFail();
    }

    private function createPaystackRecipient(Account $account, Bank $bank): array
    {
        $url = config('app.paystack_transfer_url');
        $token = config('app.paystack_secret_key');

        $headers = [
            'Accept' => 'application/json',
            'Authorization' => "Bearer {$token}",
        ];

        $fields = [
            'type' => "nuban",
            'name' => $account->account_name,
            'account_number' => $account->account_number,
            'bank_code' => $bank->code,
            'currency' => $bank->currency,
        ];

        return (new PostCurlService($url, $headers, $fields))->execute();
    }
}
