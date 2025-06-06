<?php

namespace App\Trait;

use App\Enum\AccountTransferStatus;
use Illuminate\Support\Str;
use App\Models\Account;
use App\Models\AccountTransfer;
use App\Services\Admin\PayoutService;
use Illuminate\Support\Facades\Log;

trait Transfer
{
    protected function collectRequests(): array
    {
        $requests = [];

        Account::with(['accountTransfers' => function ($query) {
            $query->where('status', AccountTransferStatus::PENDING->value)->limit(1);
        }])
        ->whereHas('accountTransfers', function ($query) {
            $query->where('status', AccountTransferStatus::PENDING->value);
        })
        ->chunk(100, function ($accounts) use (&$requests) {
            foreach ($accounts as $account) {
                $this->extractAccountRequests($account, $requests);
            }
        });

        return $requests;
    }

    protected function extractAccountRequests($account, array &$requests): void
    {
        $paymentMethod = $account->where('is_default', true)->first();

        if (!$paymentMethod) {
            return;
        }

        foreach ($account->accountTransfers as $request) {
            if ($request->status !== AccountTransferStatus::PENDING->value) {
                continue;
            }

            $amount = intval($request->amount * 100);
            if ($amount <= 0 || !$paymentMethod->recipient_code) {
                continue;
            }

            $reference = (string) Str::uuid();

            $request->update([
                'status' => AccountTransferStatus::PROCESSING->value,
                'reference' => $reference,
            ]);

            $requests[] = [
                'reference' => $reference,
                'amount' => $amount,
                'recipient' => $paymentMethod->recipient_code,
                'reason' => 'Withdrawal',
                'request_id' => $request->id,
                'account_id' => $account->id,
            ];
        }
    }

    protected function handleChunk(array $chunk): void
    {
        try {
            $result = PayoutService::paystackBulkTransfer($chunk);

            $success = $result['status'] === true;
            $data = $result['data'];

            foreach ($chunk as $item) {
                $this->handlePaystackResultItem(
                    $item,
                    $success,
                    $success ? null : $result,
                    $data,
                );
            }
        } catch (\Exception $e) {
            Log::error('Paystack bulk transfer exception: ' . $e);

            foreach ($chunk as $item) {
                $this->markRequestFailed($item['request_id'], $item['user_id'], $e->getMessage());
            }
        }
    }

    private function handlePaystackResultItem(array $item, bool $success, $errorMessage = null, $data = null): void
    {
        $request = AccountTransfer::find($item['request_id']);

        if ($success) {
            Log::info("Bulk transfer queued: Withdrawal ID {$request->id}, Ref: {$item['reference']}");
            foreach ($data as $transfer) {
                $transferCode = $transfer['transfer_code'];
                $request->update([
                    'transfer_code' => $transferCode,
                ]);
            }
        } else {
            $this->markRequestFailed($request->id, $errorMessage);
            Log::error("Failed to queue Paystack bulk transfer: " . json_encode($errorMessage));
        }
    }

    private function markRequestFailed(int $requestId, $errorMessage = null): void
    {
        $request = AccountTransfer::find($requestId);

        $request->update([
            'status' => AccountTransferStatus::FAILED->value,
            'response' => $errorMessage,
        ]);
    }

    protected function isValidTransferRequest(array $payload): bool
    {
        if (
            !isset($payload['reference']) ||
            !isset($payload['amount'])
        ) {
            return false;
        }

        $reference = $payload['reference'];
        $amount = intval($payload['amount']);

        $request = AccountTransfer::where('reference', $reference)->first();

        if (!$request) {
            return false;
        }

        if (intval($request->amount * 100) !== $amount) {
            return false;
        }

        return true;
    }

}



