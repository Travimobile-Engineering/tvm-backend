<?php

namespace App\Trait;

use App\Models\User;
use App\Enum\General;
use Illuminate\Support\Str;
use App\Enum\TransactionTitle;
use App\Models\AccountTransfer;
use App\Models\UserWithdrawLog;
use Illuminate\Support\Facades\DB;
use App\Enum\AccountTransferStatus;
use App\Enum\ChargeType;
use App\Notifications\WithdrawalNotification;
use App\Services\Admin\PayoutService;
use Illuminate\Support\Facades\Log;
use App\Notifications\WithdrawalRefundNotification;

trait Transfer
{
    // Requests from accounts
    protected function collectRequests(): array
    {
        $requests = [];

        AccountTransfer::with('account')->where('status', AccountTransferStatus::PENDING->value)
            ->chunkById(100, function ($transfers) use (&$requests) {
                foreach ($transfers as $transfer) {
                    $this->extractAccountRequests($transfer, $requests);
                }
            });

        return $requests;
    }

    protected function extractAccountRequests($transfer, array &$requests): void
    {
        $account = $transfer->account;

        // Guard clause: must have recipient_code, must be admin type
        if (! $account || ! $account->recipient_code || $account->type !== 'admin') {
            return;
        }

        if ($transfer->status !== AccountTransferStatus::PENDING->value) {
            return;
        }

        $amount = intval($transfer->amount * 100);
        if ($amount <= 0) {
            return;
        }

        $reference = (string) Str::uuid();

        $transfer->update([
            'status' => AccountTransferStatus::PROCESSING->value,
            'reference' => $reference,
        ]);

        $requests[] = [
            'reference' => $reference,
            'amount' => $amount,
            'recipient' => $account->recipient_code,
            'reason' => "Admin charges transfer",
            'request_id' => $transfer->id,
            'account_id' => $account->id,
        ];
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
            $request = UserWithdrawLog::where('reference', $reference)->first();
        }

        if (!$request) {
            return false;
        }

        if (intval($request->amount * 100) !== $amount) {
            return false;
        }

        return true;
    }

    // Requests from withdrawals
    protected function collectWithdrawRequests(): array
    {
        $requests = [];

        User::with(['userWithdrawLogs' => function ($query) {
            $query->where('status', General::PENDING)->limit(1);
        }, 'userBank'])
        ->whereHas('userWithdrawLogs', function ($query) {
            $query->where('status', General::PENDING);
        })
        ->chunk(100, function ($users) use (&$requests) {
            foreach ($users as $user) {
                $this->extractWithdrawAccountRequests($user, $requests);
            }
        });

        return $requests;
    }

    protected function extractWithdrawAccountRequests($user, array &$requests): void
    {
        $bank = $user->userBank;

        if (! $bank || ! $bank->recipient_code) {
            return;
        }

        foreach ($user->userWithdrawLogs as $withdraw) {
            if ($withdraw->status !== General::PENDING) {
                continue;
            }

            $amount = (int) round($withdraw->amount * 100);
            if ($amount <= 0) {
                continue;
            }

            $reference = (string) Str::uuid()->getHex();

            if (!preg_match('/^[a-z0-9_-]+$/', $reference)) {
                throw new \Exception("Invalid reference format: {$reference}");
            }

            $withdraw->update([
                'status' => General::PROCESSING,
                'reference' => $reference,
            ]);

            $requests[] = [
                'amount' => $amount,
                'reference' => $reference,
                'reason' => $withdraw->description ?? "User account withdrawal",
                'recipient' => $bank->recipient_code,
                'request_id' => $withdraw->id,
                'user_id' => $user->id,
            ];
        }
    }

    protected function handleUserChunk(array $chunk): void
    {
        try {
            $result = PayoutService::paystackBulkTransfer($chunk);
            $success = $result['status'] === true;
            $message = $result;

            // Handle the case where data might not be present or might contain invalid elements
            $data = $success ? ($result['data'] ?? []) : [];

            // Create a map of reference to transfer data
            $transferMap = [];
            if (is_array($data)) {
                foreach ($data as $transfer) {
                    // Skip non-array elements and elements without reference
                    if (!is_array($transfer) || !isset($transfer['reference'])) {
                        Log::warning("Invalid transfer data in Paystack response: " . json_encode($transfer));
                        continue;
                    }

                    $transferMap[$transfer['reference']] = $transfer;
                }
            }

            foreach ($chunk as $item) {
                // Find the matching transfer result for this specific item
                $matchingTransfer = $transferMap[$item['reference']] ?? null;

                $this->handleResultItem(
                    $item,
                    $success,
                    $success ? null : $message,
                    $matchingTransfer, // Pass only the relevant transfer data
                );
            }
        } catch (\Exception $e) {
            Log::error("Paystack bulk transfer exception: {$e->getMessage()}");
            foreach ($chunk as $item) {
                $this->markWithdrawRequestFailed(
                    $item['request_id'],
                    $item['user_id'],
                    [
                        'message' => $e->getMessage(),
                        'line'    => $e->getLine(),
                        'file'    => $e->getFile(),
                        'trace'   => $e->getTraceAsString(),
                    ]
                );
            }
        }
    }

    private function handleResultItem(array $item, bool $success, $errorMessage = null, $data = null): void
    {
        $withdraw = UserWithdrawLog::find($item['request_id']);
        $user = User::with(['walletAccount'])->find($item['user_id']);

        if ($success && $data) {
            Log::info("Bulk transfer queued: Withdrawal ID {$withdraw->id}, Ref: {$item['reference']}");

            $withdraw->update([
                'transfer_code' => $data['transfer_code'],
            ]);
        } else {
            $this->markWithdrawRequestFailed($withdraw->id, $user->id, $errorMessage);
            Log::error("Failed to queue Paystack bulk transfer: " . json_encode($errorMessage));
        }
    }

    private function markWithdrawRequestFailed(int $requestId, int $userId, ?array $errorMessage = null): void
    {
        DB::transaction(function () use ($requestId, $userId, $errorMessage) {
            // Use lockForUpdate to prevent race conditions
            $withdraw = UserWithdrawLog::where('id', $requestId)
                ->where('user_id', $userId)
                ->lockForUpdate()
                ->first();

            if (!$withdraw) {
                Log::error("Withdrawal already processed or not found: {$requestId}");
                return;
            }

            $user = User::where('id', $userId)
                ->lockForUpdate()
                ->with('walletAccount')
                ->first();

            if (!$user) {
                Log::error("Failed to mark withdraw request failed: Withdrawal ID {$requestId}, User ID {$userId}");
                return;
            }

            // Update withdrawal status only once
            $withdraw->update([
                'status' => General::FAILED,
                'response' => $errorMessage,
            ]);

            // Refund the user
            $this->refundUser($user, $withdraw);

            // Send notifications
            $user->notify(new WithdrawalNotification($withdraw, General::FAILED));
        });
    }

    private function refundUser(User $user, UserWithdrawLog $withdraw): void
    {
        DB::transaction(function () use ($user, $withdraw) {
            // Reload fresh instances with locks
            $freshUser = User::where('id', $user->id)
                ->with('walletAccount')
                ->first();

            if (! $freshUser) {
                Log::error("User not found for refund: {$user->id}");
                return;
            }

            $freshWithdraw = UserWithdrawLog::where('id', $withdraw->id)
                ->lockForUpdate()
                ->first();

            if (! $freshWithdraw) {
                Log::warning("Withdrawal already refunded or not found: {$withdraw->id}");
                return;
            }

            $chargeTypes = [
                ChargeType::ADMIN->value,
                ChargeType::WITHDRAW_FEE->value,
            ];

            // Refund charges
            $charges = getCharge($chargeTypes);
            $chargeAmount = array_sum($charges);
            $amount = $freshWithdraw->amount + $chargeAmount;

            // Increment earnings balance
            $freshUser->walletAccount->increment('earnings', $amount);

            // Create earning record
            $freshUser->createEarning(
                TransactionTitle::REFUND,
                $amount,
                'CR',
                General::REFUNDED,
                "Refund for failed withdrawal",
            );

            // Update withdrawal status to REFUNDED
            $freshWithdraw->update([
                'status' => General::REFUNDED,
            ]);

            // Send refund notification
            $freshUser->notify(new WithdrawalRefundNotification($freshWithdraw, General::REFUNDED));
        });
    }
}
