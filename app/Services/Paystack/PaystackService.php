<?php

namespace App\Services\Paystack;

use App\Models\User;
use App\Trait\HttpResponse;
use App\Models\AccountTransfer;
use App\Models\UserWithdrawLog;
use Illuminate\Support\Facades\DB;
use App\Enum\AccountTransferStatus;
use App\Models\AdminBulkTransfer;
use Illuminate\Support\Facades\Log;
use App\Services\Curl\PostCurlService;
use App\Notifications\WithdrawalNotification;

class PaystackService
{
    use HttpResponse;

    public static function createRecipient($user, $fields)
    {
        $url = "https://api.paystack.co/transferrecipient";
        $token = config('app.paystack_secret_key');

        $headers = [
            'Accept' => 'application/json',
            'Authorization' => "Bearer {$token}",
        ];

        $data = (new PostCurlService($url, $headers, $fields))->execute();

        $user->userBank()->update([
            'recipient_code' => $data['recipient_code'],
            'data' => $data,
        ]);
    }

    public static function transfer($user, $fields)
    {
        $url = "https://api.paystack.co/transfer";
        $token = config('app.paystack_secret_key');

        $headers = [
            'Accept' => 'application/json',
            'Authorization' => "Bearer {$token}",
        ];

        $data = (new PostCurlService($url, $headers, $fields))->execute();

        if (!is_array($data)) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid response from Paystack',
                'data' => null
            ], 500);
        }

        Log::error('Paystack Transfer Response:', $data);

        if (!isset($data['status']) || $data['status'] === false) {
            return response()->json([
                'status' => $data['status'] ?? false,
                'message' => $data['message'] ?? 'An unknown error occurred',
                'data' => null
            ], 400);
        }

        self::logWithdraw($user, $data);

        return response()->json([
            'status' => $data['status'],
            'message' => $data['message'] ?? 'Transfer successful',
            'data' => null
        ], 200);
    }

    public static function handleTransferSuccess($event): void
    {
        self::updateTransferStatus($event, AccountTransferStatus::COMPLETED, 'Transfer successful');
    }

    public static function handleTransferFailed($event): void
    {
        self::updateTransferStatus($event, AccountTransferStatus::FAILED, 'Transfer failed');
    }

    public static function handleTransferReversed($event): void
    {
        self::updateTransferStatus($event, AccountTransferStatus::REVERSED, 'Transfer reversed');
    }

    protected static function updateTransferStatus(array $event, AccountTransferStatus $status, string $logPrefix): void
    {
        $transferCode = $event['transfer_code'] ?? null;
        $reference = $event['reference'] ?? null;
        $reason = $event['reason'] ?? 'No reason provided';

        if (!$transferCode) {
            Log::error("{$logPrefix}: Missing transfer_code in event");
            return;
        }

        try {
            DB::beginTransaction();

            $bulkTransfer = AdminBulkTransfer::where('transfer_code', $transferCode)->first();
            if ($bulkTransfer) {
                $bulkTransfer->update([
                    'status' => $status->value,
                    'response' => $event,
                ]);

                // Update all associated individual transfers
                AccountTransfer::where('bulk_transfer_id', $bulkTransfer->id)
                    ->update([
                        'status' => $status->value,
                        'response' => ['bulk_reason' => $reason, 'bulk_reference' => $reference],
                    ]);

                Log::info("{$logPrefix} for AdminBulkTransfer ID {$bulkTransfer->id} - Ref: {$reference}, affecting " .
                        $bulkTransfer->accountTransfers()->count() . " transfers");
                DB::commit();
                return;
            }

            // Try to update UserWithdrawLog
            $userWithdraw = UserWithdrawLog::where('transfer_code', $transferCode)->first();
            if ($userWithdraw) {
                $userWithdraw->update([
                    'status' => $status->value,
                    'response' => $reason,
                ]);

                $user = User::find($userWithdraw->user_id);

                if (! $user) {
                    Log::error("{$logPrefix}: User not found for UserWithdrawLog ID {$userWithdraw->id}");
                    DB::commit();
                    return;
                }

                $user->notify(new WithdrawalNotification($userWithdraw, $status->value));

                Log::info("{$logPrefix} for UserWithdrawLog ID {$userWithdraw->id} - Ref: {$reference}");
                DB::commit();
                return;
            }

            Log::error("{$logPrefix}: No matching transfer record found for code: {$transferCode}");
            DB::rollBack();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("Error processing {$logPrefix}: " . $e->getMessage());
            throw $e;
        }
    }

    private static function logWithdraw($user, $data)
    {
        $user->userWithdrawLogs()->create([
            'amount' => $data['amount'],
            'transfer_code' => $data['transfer_code'],
            'status' => $data['status'],
            'data' => $data,
            'ip_address' => request()->ip(),
            'device' => request()->header('User-Agent'),
        ]);
    }
}

