<?php

namespace App\Services\Admin;

use App\Models\Fee;
use App\Models\Bank;
use App\Models\Account;
use App\Services\Curl\PostCurlService;

class AccountService
{
    public function initiateTransfer($amount)
    {
        $account = $this->findAccountByFeeIds();

        if (!empty($account->recipient_code)) {
            $this->transferToAccount($account, $amount);
            return;
        }

        $bank = $this->findBankByAccount($account);
        $data = $this->createPaystackRecipient($account, $bank);

        $account->update([
            'recipient_code' => $data['recipient_code'] ?? null,
        ]);

        $this->transferToAccount($account, $amount);
    }
    public function transferToAccount($account, $amount)
    {
        $account->accountTransfers()->create([
            'amount' => $amount,
            'status' => 'pending'
        ]);

        logger()->info('Transfer initiated successfully for processing');
    }

    private function findAccountByFeeIds(): Account
    {
        $feeIds = Fee::pluck('id');

        return Account::where(function ($query) use ($feeIds) {
            foreach ($feeIds as $id) {
                $query->orWhereJsonContains('fees', $id);
            }
        })->firstOrFail();
    }

    private function findBankByAccount(Account $account): Bank
    {
        return Bank::where('name', $account->bank_name)->firstOrFail();
    }

    private function createPaystackRecipient(Account $account, Bank $bank): array
    {
        $url = "https://api.paystack.co/transferrecipient";
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
