<?php

namespace App\Services;

use App\Models\Bank;
use App\Models\User;
use App\Enum\General;
use App\Enum\UserType;
use App\Models\Wallet;
use App\Models\Earning;
use App\Enum\PaymentType;
use App\Trait\DriverTrait;
use App\Models\Transaction;
use App\Trait\HttpResponse;
use App\Events\WalletFunded;
use App\Enum\TransactionTitle;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Services\ERP\ChargeService;
use App\DTO\NotificationDispatchData;
use App\Services\Paystack\PaystackService;
use Unicodeveloper\Paystack\Facades\Paystack;
use App\Services\Notification\NotificationDispatcher;
use App\Http\Controllers\Payment\PaystackPaymentController;
use App\Models\UserBank;
use App\Services\Curl\PostCurlService;

class WalletService
{
    use HttpResponse, DriverTrait;

    protected $user;

    public function __construct(
        protected NotificationDispatcher $notifier
    ){
        $this->user = JWTAuth::user();
    }

    public function getBalance()
    {
        $userId = request()->input('userId') ?? $this->user?->id;

        $user = User::find($userId);

        if (!$user) {
            return $this->error("User not found", 404);
        }

        $data = $user->wallet_amount ?? [];

        return $this->success($data, "Wallet balance retrieved");
    }

    public function fundWallet($request)
    {
        $ppc = new PaystackPaymentController();

        $response = $ppc->verifyTransaction($request->reference, $request->amount);

        if ($response['status'] === 'success') {
            $user = User::findOrFail($this->user->id);

            Transaction::create([
                'user_id' => $user->id,
                'title' => 'Wallet top up',
                'amount' => $request->amount,
                'type' => 'CR',
                'txn_reference' => $request->reference
            ]);

            $formattedAmount = number_format($request->amount);

            $this->userIncrementBalance($user, $formattedAmount);

            $this->notifier->send(new NotificationDispatchData(
                events: [
                    [
                        'class' => WalletFunded::class,
                        'payload' => [
                            'type' => 'wallet_funded',
                            'message' => "₦{$formattedAmount} has been added to your wallet.",
                            'userId' => $user->id,
                            'amount' => $request->amount,
                        ],
                    ],
                ],
                recipients: $user,
                title: 'Wallet Funded',
                body: "₦{$formattedAmount} has been added to your wallet.",
                data: [
                    'type' => 'wallet_funded',
                    'amount' => $request->amount,
                ]
            ));

            return $this->success($user, "Wallet funded successfully");
        } else {
            return $this->error(null, $response['message'], 400);
        }
    }

    public function transfer($request)
    {
        $sender = $this->user;

        $recipient = User::where('agent_id', $request->agent_id)->first();
        if (! $recipient) {
            return $this->error(null, "You are not authorized to perform this action", 403);
        }
        if ($sender->id === $recipient->id) {
            return $this->error(null, "Cannot transfer to self", 400);
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
                    throw new \RuntimeException("Insufficient wallet balance");
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
                );

                $recipient->createTransaction(
                    TransactionTitle::CREDIT->value,
                    $amount,
                    'CR',
                    $reference,
                );
            }, attempts: 3);
        } catch (\RuntimeException $e) {
            return $this->error(null, $e->getMessage(), 400);
        } catch (\Throwable $e) {
            return $this->error(null, "Something went wrong: " . $e->getMessage(), 400);
        }

        return $this->success(null, "Funds tranfered successfully");
    }

    public function getTransactions()
    {
        $userId = request()->input('userId') ?? $this->user?->id;

        $user = User::with('transactions')->find($userId);

        if (!$user) {
            return $this->error("User not found", 404);
        }

        $data = $user->transactions ?? [];

        return $this->success($data, "Wallet transactions retrieved");
    }

    public function walletSetup($request, $sendVerificationCode)
    {
        $user = User::with(['userBank', 'userPin', 'userTransferReceipient'])
            ->findOrFail($request->user_id);

        $code = getCode();

        try {
            DB::beginTransaction();

            if (!empty($user->userBank)) {
                if ($user->userPin?->status === 'active') {
                    return $this->error(null, "Your bank is already active. You cannot create a new bank!", 403);
                }

                if ($user->userPin?->status === 'pending') {
                    $user->update([
                        'verification_code' => $code,
                        'verification_code_expires_at' => now()->addMinutes(30),
                    ]);

                    $sendVerificationCode->execute($user, $code);

                    DB::commit();
                    return $this->success(null, "Verification email resent successfully", 200);
                }

                return $this->error(null, "You have already created a bank.", 403);
            }

            $user->userBank()->create([
                'bank_name' => $request->bank_name,
                'account_number' => $request->account_number,
                'account_name' => $request->account_name,
                'is_default' => true,
            ]);

            $bank = Bank::where([
                'name' => $request->bank_name
            ])->first();

            if(! $bank) {
                return $this->error(null, "Selected bank not found!", 404);
            }

            $fields = [
                'type' => "nuban",
                'name' => $request->account_name,
                'account_number' => $request->account_number,
                'bank_code' => $bank->code,
                'currency' => $bank->currency
            ];

            PaystackService::createRecipient($user, $fields);

            if(empty($user->userPin)) {
                $user->userPin()->create([
                    'pin' => bcrypt($request->pin),
                    'ip_address' => $request->ip(),
                    'device_info' => $request->header('User-Agent'),
                    'attempts' => 0
                ]);
            }

            $user->update([
                'verification_code' => $code,
                'verification_code_expires_at' => now()->addMinutes(30),
            ]);

            DB::commit();

            $sendVerificationCode->execute($user, $code);

            return $this->success(null, "Created successfully", 201);
        } catch (\Throwable $th) {
            DB::rollBack();

            throw $th;
        }
    }

    public function changeBank($request)
    {
        $user = User::with(['userBank', 'userPin', 'userTransferReceipient'])
            ->findOrFail($request->user_id);

        $user->userBank()->update([
            'bank_name' => $request->bank_name,
            'account_number' => $request->account_number,
            'account_name' => $request->account_name,
            'is_default' => true,
        ]);

        $bank = Bank::where([
            'name' => $request->bank_name
        ])->first();

        if(! $bank) {
            return $this->error(null, "Selected bank not found!", 404);
        }

        $fields = [
            'type' => "nuban",
            'name' => $request->account_name,
            'account_number' => $request->account_number,
            'bank_code' => $bank->code,
            'currency' => $bank->currency
        ];

        PaystackService::createRecipient($user, $fields);

        return $this->success(null, "Created successfully", 201);
    }

    public function verifyPin($request)
    {
        $user = User::with('userPin')
            ->where('verification_code', $request->code)
            ->where('verification_code_expires_at', '>', now())
            ->find($request->user_id);


        if (! $user) {
            return $this->error(null, "Invalid code!", 400);
        }

        $user->update([
            'verification_code' => 0,
            'verification_code_expires_at' => null,
        ]);

        $user->userPin()->update([
            'status' => 'active'
        ]);

        return $this->success(null, "Setup successfully");
    }

    public function setTransactionPin($request)
    {
        $user = User::with(['userPin'])
            ->findOrFail($request->user_id);

        if ($user->userPin) {
            return $this->error(null, "You have already set a transaction pin", 403);
        }

        $user->userPin()->create([
            'pin' => bcrypt($request->pin),
            'ip_address' => $request->ip(),
            'device_info' => $request->header('User-Agent'),
            'attempts' => 0,
            'status' => General::ACTIVE,
        ]);

        return $this->success(null, "Transaction pin set successfully", 201);
    }

    public function withdraw($request)
    {
        $user = User::with(['userPin', 'walletAccount', 'userWithdrawLogs', 'userBank'])
            ->findOrFail($request->user_id);

        if (! $user->userBank) {
            return $this->error(null, "No bank found", 404);
        }

        if (! $user->walletAccount) {
            return $this->error(null, "Wallet not found", 404);
        }

        if ($user->user_category === UserType::AGENT->value && (float) $user->earning_balance < 5000) {
            return $this->error(null, "You must have at least ₦5,000 in your earning balance to withdraw", 400);
        }

        if(empty($user?->userPin?->pin)) {
            return $this->error(null,  "Set your transaction pin!", 400);
        }

        try {
            DB::transaction(function () use ($user, $request) {
                $wallet = $user->walletAccount()
                    ->lockForUpdate()
                    ->firstOrFail();

                $current = $wallet->earnings;
                $amount  = (float) $request->amount;

                if ($amount < 100.00) {
                    throw new \RuntimeException("Minimum withdrawal amount is 100");
                }

                if ($amount > $current) {
                    throw new \RuntimeException("Insufficient earning balance");
                }

                $newBalance = $current - $amount;

                $wallet->update(['earnings' => $newBalance]);

                $user->userWithdrawLogs()->create([
                    'amount' => $request->amount,
                    'previous_balance' => $user->earning_balance,
                    'new_balance' => $newBalance,
                    'status' => General::PENDING,
                    'ip_address' => $request->ip(),
                    'device' => $request->header('User-Agent'),
                ]);

                $user->createEarning(TransactionTitle::WITHDRAWAL->value, $request->amount, 'DR', General::PAID);

                // Admin Charge
                app(ChargeService::class)->adminCharge($user);
            }, attempts: 3);
        } catch (\RuntimeException $e) {
            return $this->error(null, $e->getMessage(), 400);
        } catch (\Throwable $e) {
            return $this->error(null, "Something went wrong: " . $e->getMessage(), 400);
        }

        return $this->success(null, "Request sent successfully");
    }

    public function balanceWithdraw($request)
    {
        $user = User::with(['userPin', 'walletAccount', 'userWithdrawLogs'])
            ->findOrFail($request->user_id);

        if (!$user->walletAccount) {
            return $this->error(null, "Wallet not found", 404);
        }

        if ($user->user_category === UserType::AGENT->value && (float) $user->earning_balance < 5000) {
            return $this->error(null, "You must have at least ₦5,000 in your earning balance to withdraw", 400);
        }

        if(empty($user?->userPin?->pin)) {
            return $this->error(null,  "Set your transaction pin!", 400);
        }

        try {
            DB::transaction(function () use ($user, $request) {
                // Lock wallet row and re-read fresh
                /** @var \App\Models\Wallet $wallet */
                $wallet = $user->walletAccount()->lockForUpdate()->firstOrFail();

                $amount = (float) $request->amount;
                if ($amount <= 0) {
                    throw new \RuntimeException("Invalid amount");
                }
                if ($amount > (float) $wallet->earnings) {
                    throw new \RuntimeException("Insufficient earning balance");
                }

                // Now you can keep your original calls, but they’ll act on the locked row
                $this->driverDecrementEarning($user, $amount, $wallet);

                $user->createEarning(
                    TransactionTitle::WITHDRAWAL->value,
                    $amount,
                    'DR',
                    General::PAID,
                    "Withdrawal to wallet successful"
                );

                $this->userIncrementBalance($user, $amount, $wallet);

                // If adminCharge mutates balances/fees, keep it inside the TX
                app(ChargeService::class)->adminCharge($user);
            }, attempts: 3);
        } catch (\RuntimeException $e) {
            return $this->error(null, $e->getMessage(), 400);
        } catch (\Throwable $e) {
            return $this->error(null, "Something went wrong: " . $e->getMessage(), 400);
        }

        return $this->success(null, "Withdrawal to wallet successful");
    }

    public function recentTransaction($userId)
    {
        $date = request()->input('date');

        $transactions = Transaction::where(function ($query) use ($userId) {
                $query->where('user_id', $userId)
                    ->orWhere('receiver_id', $userId);
            })
            ->select('id', 'user_id', 'title', 'amount', 'type', 'status', 'created_at')
            ->when($date, fn($query) => $query->whereDate('created_at', $date))
            ->orderByDesc('created_at')
            ->paginate(25)
            ->through(fn($transaction) => [
                'id' => $transaction->id,
                'title' => $transaction->title,
                'amount' => $transaction->amount,
                'type' => $transaction->type,
                'status' => $transaction->status,
                'created_at' => $transaction->created_at,
            ]);

        return $this->withPagination($transactions, "Recent transactions");
    }

    public function recentEarning($userId)
    {
        $date = request()->input('date');

        $earnings = Earning::select('id', 'title', 'amount', 'type', 'description', 'status', 'created_at')
            ->where('user_id', $userId)
            ->when($date, fn($query) => $query->whereDate('created_at', $date))
            ->latest()
            ->paginate(25);

        return $this->withPagination($earnings, "Recent earnings");
    }

    public function walletTopUp($request)
    {
        User::findOrFail($request->input('user_id'));

        $amount = $request->input('amount') * 100;

        $callbackUrl = $request->input('redirect_url');
        if (!filter_var($callbackUrl, FILTER_VALIDATE_URL)) {
            return response()->json(['error' => 'Invalid callback URL'], 400);
        }

        $paymentDetails = [
            'email' => $request->input('email') ?? "support@travimobile.com",
            'amount' => $amount,
            'metadata' => json_encode([
                'user_id' => $request->input('user_id'),
                'payment_type' => PaymentType::FUND_WALLET,
            ]),
            'callback_url' => (string) trim($request->input('redirect_url')),
        ];

        $paystackInstance = Paystack::getAuthorizationUrl($paymentDetails);

        return [
            'status' => 'success',
            'data' => $paystackInstance,
        ];
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

        return $this->success($statistics, "Transaction statistics retrieved successfully.");
    }

    public function updateRecipientCode()
    {
        $url = "https://api.paystack.co/transferrecipient";
        $token = config('app.paystack_secret_key');

        $headers = [
            'Accept' => 'application/json',
            'Authorization' => "Bearer {$token}",
        ];

        $banks = Bank::all()->keyBy('name');
        $userBanks = UserBank::whereNull('recipient_code')->get();

        foreach($userBanks as $userBank) {
            $bank = $banks->get($userBank->bank_name);

            if(! $bank) {
                continue;
            }

            $fields = [
                'type' => "nuban",
                'name' => $userBank->account_name,
                'account_number' => $userBank->account_number,
                'bank_code' => $bank->code,
                'currency' => $bank->currency
            ];

            $data = (new PostCurlService($url, $headers, $fields))->execute();

            $userBank->update([
                'recipient_code' => $data['recipient_code'],
                'data' => $data,
            ]);
        }

        UserBank::where('is_default', false)->update([
            'is_default' => true,
        ]);

        return $this->success(null, "Recipient code updated successfully.");
    }
}
