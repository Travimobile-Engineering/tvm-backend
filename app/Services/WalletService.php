<?php

namespace App\Services;

use App\DTO\NotificationDispatchData;
use App\Enum\ChargeType;
use App\Enum\General;
use App\Enum\PaymentType;
use App\Enum\TransactionTitle;
use App\Enum\UserType;
use App\Http\Controllers\Payment\PaystackPaymentController;
use App\Models\Bank;
use App\Models\Earning;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WithdrawalRestriction;
use App\Services\Client\HttpService;
use App\Services\Client\RequestOptions;
use App\Services\Notification\NotificationDispatcher;
use App\Services\Paystack\PaystackService;
use App\Trait\ChargeTrait;
use App\Trait\DriverTrait;
use App\Trait\HttpResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;

class WalletService
{
    const MIN_WITHDRAWAL = 100;

    use ChargeTrait, DriverTrait, HttpResponse;

    protected $user;

    public function __construct(
        protected NotificationDispatcher $notifier,
        protected HttpService $httpService
    ) {
        $this->user = JWTAuth::user();
    }

    public function getBalance()
    {
        $userId = request()->input('userId') ?? $this->user?->id;

        $user = User::find($userId);

        if (! $user) {
            return $this->error('User not found', 404);
        }

        $data = $user->wallet_amount ?? [];

        return $this->success($data, 'Wallet balance retrieved');
    }

    public function fundWallet($request)
    {
        $ppc = new PaystackPaymentController;

        $response = $ppc->verifyTransaction($request->reference, $request->amount);

        if ($response['status'] === 'success') {
            $user = User::findOrFail($this->user->id);

            Transaction::create([
                'user_id' => $user->id,
                'title' => 'Wallet top up',
                'amount' => $request->amount,
                'type' => 'CR',
                'txn_reference' => $request->reference,
            ]);

            $formattedAmount = number_format($request->amount);

            $this->userIncrementBalance($user, $formattedAmount);

            $this->notifier->send(new NotificationDispatchData(
                events: [],
                recipients: $user,
                title: 'Wallet Funded',
                body: "₦{$formattedAmount} has been added to your wallet.",
                data: [
                    'type' => 'wallet_funded',
                    'amount' => $request->amount,
                ]
            ));

            return $this->success($user, 'Wallet funded successfully');
        } else {
            return $this->error(null, $response['message'], 400);
        }
    }

    public function transfer($request)
    {
        $sender = $this->user;

        $recipient = User::where('agent_id', $request->agent_id)->first();
        if (! $recipient) {
            return $this->error(null, 'You are not authorized to perform this action', 403);
        }
        if ($sender->id === $recipient->id) {
            return $this->error(null, 'Cannot transfer to self', 400);
        }

        $amount = (float) $request->amount;

        try {
            DB::transaction(function () use ($sender, $recipient, $amount) {
                // Lock both wallet rows in a consistent order to prevent deadlocks
                [$firstUser, $secondUser] = $sender->id < $recipient->id
                    ? [$sender, $recipient]
                    : [$recipient, $sender];

                // Lock first wallet
                $firstWallet = Wallet::where('user_id', $firstUser->id)->lockForUpdate()->first();

                // Lock second wallet
                $secondWallet = Wallet::where('user_id', $secondUser->id)->lockForUpdate()->first();

                // Map locked wallets back to sender/recipient
                $senderWallet = $sender->id === $firstUser->id ? $firstWallet : $secondWallet;
                $recipientWallet = $recipient->id === $firstUser->id ? $firstWallet : $secondWallet;

                // Balance check using the LOCKED sender wallet
                if ($amount > (float) $senderWallet->balance) {
                    throw new \RuntimeException('Insufficient wallet balance');
                }

                // Move funds atomically while locks are held
                $this->userDecrementBalance($sender, $amount, $senderWallet);
                $this->userIncrementBalance($recipient, $amount, $recipientWallet);

                // Single reference for both sides
                $reference = generateReference('TRF', 'transactions');

                // Ledger entries
                $sender->createTransaction(
                    TransactionTitle::TRANSFER_WALLET->value,
                    $amount,
                    'DR',
                    $reference,
                    $recipient->id,
                    "Transfer from {$sender->first_name} to {$recipient->first_name}"
                );

                $recipient->createTransaction(
                    TransactionTitle::CREDIT->value,
                    $amount,
                    'CR',
                    $reference,
                    null,
                    "Transfer from {$sender->first_name} to {$recipient->first_name}"
                );
            }, attempts: 3);
        } catch (\RuntimeException $e) {
            return $this->error(null, $e->getMessage(), 400);
        } catch (\Throwable $e) {
            return $this->error(null, 'Something went wrong: '.$e->getMessage(), 400);
        }

        return $this->success(null, 'Funds tranfered successfully');
    }

    public function getTransactions()
    {
        $userId = request()->input('userId') ?? $this->user?->id;

        $user = User::with('transactions')->find($userId);

        if (! $user) {
            return $this->error('User not found', 404);
        }

        $data = $user->transactions ?? [];

        return $this->success($data, 'Wallet transactions retrieved');
    }

    public function walletSetup($request, $sendVerificationCode)
    {
        $user = User::with(['userBank', 'userPin', 'userTransferReceipient'])
            ->findOrFail($request->user_id);

        $code = getCode();

        try {
            DB::beginTransaction();

            if (! empty($user->userBank)) {
                if ($user->userPin?->status === 'active') {
                    return $this->error(null, 'Your bank is already active. You cannot create a new bank!', 403);
                }

                if ($user->userPin?->status === 'pending') {
                    $user->update([
                        'verification_code' => $code,
                        'verification_code_expires_at' => now()->addMinutes(30),
                    ]);

                    if (app()->environment('production')) {
                        $sendVerificationCode->execute($user, $code);
                    }

                    DB::commit();

                    return $this->success(null, 'Verification email resent successfully', 200);
                }

                return $this->error(null, 'You have already created a bank.', 403);
            }

            $user->userBank()->create([
                'bank_name' => $request->bank_name,
                'account_number' => $request->account_number,
                'account_name' => $request->account_name,
                'is_default' => true,
            ]);

            $bank = Bank::where([
                'name' => $request->bank_name,
            ])->first();

            if (! $bank) {
                return $this->error(null, 'Selected bank not found!', 404);
            }

            $fields = [
                'type' => 'nuban',
                'name' => $request->account_name,
                'account_number' => $request->account_number,
                'bank_code' => $bank->code,
                'currency' => $bank->currency,
            ];

            $recipientData = PaystackService::createRecipient($fields);

            if (! $recipientData['status']) {
                return $this->error(null, $recipientData['message'], 400);
            }

            if (empty($user->userPin)) {
                $user->userPin()->create([
                    'pin' => bcrypt($request->pin),
                    'ip_address' => $request->ip(),
                    'device_info' => $request->header('User-Agent'),
                    'attempts' => 0,
                ]);
            }

            $user->update([
                'verification_code' => $code,
                'verification_code_expires_at' => now()->addMinutes(30),
            ]);

            $user->userBank()->update([
                'recipient_code' => $recipientData['data']['recipient_code'],
                'data' => $recipientData['data'],
            ]);

            DB::commit();

            if (app()->environment('production')) {
                $sendVerificationCode->execute($user, $code);
            }

            return $this->success(null, 'Created successfully', 201);
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    public function changeBank($request)
    {
        $user = User::with(['userBank', 'userPin', 'userTransferReceipient'])
            ->findOrFail($request->user_id);

        $bank = Bank::where([
            'name' => $request->bank_name,
        ])->first();

        if (! $bank) {
            return $this->error(null, 'Selected bank not found!', 404);
        }

        $fields = [
            'type' => 'nuban',
            'name' => $request->account_name,
            'account_number' => $request->account_number,
            'bank_code' => $bank->code,
            'currency' => $bank->currency,
        ];

        $recipientData = PaystackService::createRecipient($fields);

        if (! $recipientData['status']) {
            return $this->error(null, $recipientData['message'], 400);
        }

        $user->userBank()->update([
            'bank_name' => $request->bank_name,
            'account_number' => $request->account_number,
            'account_name' => $request->account_name,
            'is_default' => true,
            'recipient_code' => $recipientData['data']['recipient_code'],
            'data' => $recipientData['data'],
        ]);

        return $this->success(null, 'Created successfully', 201);
    }

    public function verifyPin($request)
    {
        $user = User::with('userPin')
            ->where('verification_code', $request->code)
            ->where('verification_code_expires_at', '>', now())
            ->find($request->user_id);

        if (! $user) {
            return $this->error(null, 'Invalid code!', 400);
        }

        $user->update([
            'verification_code' => 0,
            'verification_code_expires_at' => null,
        ]);

        $user->userPin()->update([
            'status' => 'active',
        ]);

        return $this->success(null, 'Setup successfully');
    }

    public function setTransactionPin($request)
    {
        $user = User::with(['userPin'])
            ->findOrFail($request->user_id);

        if ($user->userPin) {
            return $this->error(null, 'You have already set a transaction pin', 403);
        }

        $user->userPin()->create([
            'pin' => bcrypt($request->pin),
            'ip_address' => $request->ip(),
            'device_info' => $request->header('User-Agent'),
            'attempts' => 0,
            'status' => General::ACTIVE,
        ]);

        return $this->success(null, 'Transaction pin set successfully', 201);
    }

    public function withdraw($request)
    {
        $user = User::with(['userPin', 'walletAccount', 'userWithdrawLogs', 'userBank'])
            ->findOrFail($request->user_id);

        if (! $user->userBank) {
            return $this->error(null, 'No bank found', 404);
        }

        if (! $user->walletAccount) {
            return $this->error(null, 'Wallet not found', 404);
        }

        if ($user->walletAccount->is_flagged) {
            return $this->error(null, "You can't withdraw funds at this time, contact support.", 403);
        }

        if (empty($user?->userPin?->pin)) {
            return $this->error(null, 'Set your transaction pin!', 400);
        }

        // Check withdrawal restrictions
        $restrictionCheck = $this->checkWithdrawalRestrictions($user, $request->amount);
        if (! $restrictionCheck['allowed']) {
            return $this->error(null, $restrictionCheck['message'], 400);
        }

        $charges = getCharge([
            ChargeType::ADMIN->value,
            ChargeType::WITHDRAW_FEE->value,
        ]);

        try {
            DB::transaction(function () use ($user, $request, $charges) {
                $wallet = $user->walletAccount()
                    ->lockForUpdate()
                    ->firstOrFail();

                $totalDeduction = $request->amount + $charges[ChargeType::WITHDRAW_FEE->value] + $charges[ChargeType::ADMIN->value];

                if ($request->amount < self::MIN_WITHDRAWAL) {
                    throw new \RuntimeException('Minimum withdrawal amount is '.self::MIN_WITHDRAWAL);
                }

                if ($totalDeduction > $wallet->earnings) {
                    throw new \RuntimeException('Insufficient earning balance');
                }

                $fees = $request->amount + $charges[ChargeType::WITHDRAW_FEE->value];
                $newBalance = $wallet->earnings - $fees;

                $wallet->update(['earnings' => $newBalance]);

                $user->userWithdrawLogs()->create([
                    'amount' => $request->amount,
                    'previous_balance' => $user->earning_balance,
                    'new_balance' => $newBalance,
                    'status' => General::PENDING,
                    'ip_address' => $request->ip(),
                    'device' => $request->header('User-Agent'),
                    'description' => 'User account withdrawal',
                ]);

                $user->createEarning(
                    TransactionTitle::WITHDRAWAL->value,
                    $request->amount,
                    'DR',
                    General::PAID,
                    'Withdrawal amount charged from your earnings.'
                );

                $user->createEarning(
                    TransactionTitle::WITHDRAW_FEE->value,
                    $charges[ChargeType::WITHDRAW_FEE->value],
                    'DR',
                    General::PAID,
                    'Withdraw fee charged from your earnings.'
                );

                // Admin Charge
                $this->recordCharges($request, $user, 'earnings');
            }, attempts: 3);
        } catch (\RuntimeException $e) {
            return $this->error(null, $e->getMessage(), 400);
        } catch (\Throwable $e) {
            return $this->error(null, 'Something went wrong: '.$e->getMessage(), 400);
        }

        return $this->success(null, 'Request sent successfully');
    }

    public function balanceWithdraw($request)
    {
        $user = User::with(['userPin', 'walletAccount', 'userWithdrawLogs'])
            ->findOrFail($request->user_id);

        if (! $user->walletAccount) {
            return $this->error(null, 'Wallet not found', 404);
        }

        if ($user->walletAccount->is_flagged) {
            return $this->error(null, "You can't withdraw at this time, contact support.", 403);
        }

        if ($user->user_category === UserType::AGENT->value && (float) $user->earning_balance < 5000) {
            return $this->error(null, 'You must have at least ₦5,000 in your earning balance to withdraw', 400);
        }

        if (empty($user?->userPin?->pin)) {
            return $this->error(null, 'Set your transaction pin!', 400);
        }

        // Check withdrawal restrictions
        $restrictionCheck = $this->checkWithdrawalRestrictions($user, $request->amount);
        if (! $restrictionCheck['allowed']) {
            return $this->error(null, $restrictionCheck['message'], 400);
        }

        $charges = getCharge([ChargeType::ADMIN->value]);

        try {
            DB::transaction(function () use ($user, $request, $charges) {
                // Lock wallet row and re-read fresh
                /** @var Wallet $wallet */
                $wallet = $user->walletAccount()->lockForUpdate()->firstOrFail();

                $totalDeduction = $request->amount + $charges[ChargeType::ADMIN->value];

                $amount = (float) $request->amount;
                if ($amount <= 0) {
                    throw new \RuntimeException('Invalid amount');
                }

                if ($totalDeduction > (float) $wallet->earnings) {
                    throw new \RuntimeException('Insufficient earning balance');
                }

                $this->driverDecrementEarning($user, $amount, $wallet);

                $user->createEarning(
                    TransactionTitle::WITHDRAWAL->value,
                    $amount,
                    'DR',
                    General::PAID,
                    'Withdrawal to wallet successful'
                );

                $this->userIncrementBalance($user, $amount, $wallet);

                $user->createTransaction(
                    TransactionTitle::TOP_UP->value,
                    $amount,
                    'CR',
                    generateReference('TRF', 'transactions'),
                    null,
                    'Withdrawal to wallet successful'
                );

                // Admin charge
                $this->recordCharges($request, $user, 'earnings');
            }, attempts: 3);
        } catch (\RuntimeException $e) {
            return $this->error(null, $e->getMessage(), 400);
        } catch (\Throwable $e) {
            return $this->error(null, 'Something went wrong: '.$e->getMessage(), 400);
        }

        return $this->success(null, 'Withdrawal to wallet successful');
    }

    public function recentTransaction($userId)
    {
        $date = request()->input('date');

        $transactions = Transaction::where(function ($query) use ($userId) {
            $query->where('user_id', $userId)
                ->orWhere('receiver_id', $userId);
        })
            ->select('id', 'user_id', 'title', 'amount', 'type', 'status', 'created_at')
            ->when($date, fn ($query) => $query->whereDate('created_at', $date))
            ->orderByDesc('created_at')
            ->paginate(25)
            ->through(fn ($transaction) => [
                'id' => $transaction->id,
                'title' => $transaction->title,
                'amount' => $transaction->amount,
                'type' => $transaction->type,
                'status' => $transaction->status,
                'created_at' => $transaction->created_at,
            ]);

        return $this->withPagination($transactions, 'Recent transactions');
    }

    public function recentEarning($userId)
    {
        $date = request()->input('date');

        $earnings = Earning::select('id', 'title', 'amount', 'type', 'description', 'status', 'created_at')
            ->where('user_id', $userId)
            ->when($date, fn ($query) => $query->whereDate('created_at', $date))
            ->latest()
            ->paginate(25);

        return $this->withPagination($earnings, 'Recent earnings');
    }

    public function walletTopUp($request)
    {
        User::findOrFail($request->input('user_id'));

        $amount = $request->input('amount') * 100;

        $callbackUrl = $request->input('redirect_url');
        if (! filter_var($callbackUrl, FILTER_VALIDATE_URL)) {
            return response()->json(['error' => 'Invalid callback URL'], 400);
        }

        $paymentDetails = [
            'email' => $request->input('email') ?? 'hello@email.com',
            'amount' => $amount,
            'currency' => 'NGN',
            'metadata' => json_encode([
                'user_id' => $request->input('user_id'),
                'payment_type' => PaymentType::FUND_WALLET,
                'service' => 'transport',
            ]),
            'payment_method' => 'paystack',
            'callback_url' => (string) trim($request->input('redirect_url')),
        ];

        try {
            $url = config('services.payment.url').'/paystack/initialize';
            $response = $this->httpService->post(
                $url,
                new RequestOptions(
                    data: $paymentDetails
                )
            );

            if ($response->failed()) {
                return [
                    'status' => false,
                    'message' => $response['message'] ?? 'Failed to initialize payment',
                    'data' => null,
                ];
            }

            $data = $response->json();

            return [
                'status' => 'success',
                'message' => $data['message'],
                'data' => $data['data'],
            ];
        } catch (\Exception $e) {
            return $this->error(null, $e->getMessage(), 500);
        }
    }

    public function stats($userId)
    {
        $startDate = Carbon::parse(request()->input('start_date') ?: now()->startOfWeek())->startOfDay();
        $endDate = Carbon::parse(request()->input('end_date') ?: now()->endOfWeek())->endOfDay();

        $transactions = Transaction::where('user_id', $userId)
            ->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate)
            ->selectRaw("
                DATE(created_at) as transaction_date,
                SUM(CASE WHEN type = 'CR' THEN amount ELSE 0 END) as inflow,
                SUM(CASE WHEN type = 'DR' THEN amount ELSE 0 END) as outflow
            ")
            ->groupBy('transaction_date')
            ->get()
            ->keyBy(function ($transaction) {
                return Carbon::parse($transaction->transaction_date)->format('Y-m-d');
            });

        $allDates = collect();
        $currentDate = $startDate->copy();

        while ($currentDate <= $endDate) {
            $allDates->push($currentDate->format('Y-m-d'));
            $currentDate->addDay();
        }

        $statistics = $allDates->map(function ($date) use ($transactions) {
            return [
                'date' => $date,
                'inflow' => (int) ($transactions[$date]->inflow ?? 0),
                'outflow' => (int) ($transactions[$date]->outflow ?? 0),
            ];
        });

        return $this->success($statistics, 'Transaction statistics retrieved successfully.');
    }

    public function getCharges()
    {
        $type = request()->query('type', null);
        $userId = request()->query('user_id', null);

        if (! $type || ! $userId) {
            return $this->error('Please provide a charge type and user ID.', 400);
        }

        return match ($type) {
            'booking' => $this->bookingCharge($userId),
            'bank_withdrawal' => $this->bankWithdrawalCharge(),
            'wallet_withdrawal' => $this->walletWithdrawalCharge(),
            default => $this->error(null, 'Invalid charge type.', 400),
        };
    }

    public function checkWithdrawalRestrictions(User $user, float $amount): array
    {
        // Global minimum withdrawal check
        if ($amount < self::MIN_WITHDRAWAL) {
            return [
                'allowed' => false,
                'message' => 'Minimum withdrawal amount is ₦'.self::MIN_WITHDRAWAL,
            ];
        }

        // Check active restrictions for this user type
        $restrictions = WithdrawalRestriction::getActiveRestrictions();

        foreach ($restrictions as $restriction) {
            if ($restriction->appliesToUserType($user->user_category)) {
                // Complete block (regardless of balance)
                if ($restriction->complete_block) {
                    return [
                        'allowed' => false,
                        'message' => $restriction->message,
                    ];
                }

                // Minimum balance check
                if ($user->earning_balance < $restriction->min_balance) {
                    $message = str_replace(
                        '{min_balance}',
                        number_format((int) $restriction->min_balance, 2),
                        $restriction->message
                    );

                    return [
                        'allowed' => false,
                        'message' => $message,
                    ];
                }
            }
        }

        return ['allowed' => true, 'message' => null];
    }
}
