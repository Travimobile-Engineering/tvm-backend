<?php

namespace App\Services\Admin;

use App\Models\Fee;
use App\Models\Bank;
use App\Models\Account;
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
                'recipient_code' => $data['recipient_code'] ?? null,
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

    public function updateAccountRecipient()
    {
        Account::whereNull('recipient_code')
            ->chunk(100, function ($accounts) {
                foreach ($accounts as $account) {
                    try {
                        $bank = $this->findBankByAccount($account);
                        $data = $this->createPaystackRecipient($account, $bank);

                        $account->update([
                            'recipient_code' => $data['recipient_code'] ?? null,
                        ]);
                    } catch (\Exception $e) {
                        \Log::error("Failed to update recipient for account {$account->id}: " . $e->getMessage());
                        continue;
                    }
                }
            });

        return response()->json(['message' => 'Recipient update process completed.']);
    }
}
