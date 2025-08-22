<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Models\Bank;
use App\Models\UserBank;
use App\Services\Curl\PostCurlService;

class UpdateUserBankRecipientsJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        UserBank::whereNull('recipient_code')
            ->chunk(100, function ($userbanks) {
                foreach ($userbanks as $userbank) {
                    try {
                        $bank = Bank::where('name', $userbank->bank_name)->first();
                        if (!$bank) {
                            continue;
                        }

                        $url = config('app.paystack_transfer_url');
                        $token = config('app.paystack_secret_key');

                        $headers = [
                            'Accept' => 'application/json',
                            'Authorization' => "Bearer {$token}",
                        ];

                        $fields = [
                            'type' => "nuban",
                            'name' => $userbank->account_name,
                            'account_number' => $userbank->account_number,
                            'bank_code' => $bank->code,
                            'currency' => $bank->currency,
                        ];

                        $data = (new PostCurlService($url, $headers, $fields))->execute();

                        $userbank->update([
                            'recipient_code' => $data['recipient_code'] ?? null,
                        ]);
                    } catch (\Exception $e) {
                        continue;
                    }
                }
            });
    }
}
