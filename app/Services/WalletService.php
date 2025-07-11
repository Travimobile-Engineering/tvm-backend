<?php

namespace App\Services;

use App\DTO\NotificationDispatchData;
use App\Enum\General;
use App\Models\Bank;
use App\Models\User;
use App\Enum\PaymentType;
use App\Events\WalletFunded;
use App\Mail\VerifyPinMail;
use App\Models\Transaction;
use App\Trait\HttpResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Http\Controllers\Payment\PaystackPaymentController;
use App\Models\TripPayment;
use App\Services\Notification\NotificationDispatcher;
use App\Services\Paystack\PaystackService;
use App\Trait\DriverTrait;
use Unicodeveloper\Paystack\Facades\Paystack;

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
        $user = User::where('agent_id', $request->agent_id)
            ->first();

        if(!$user) {
            return $this->error(null, "You are not authorized to perform this action", 403);
        }

        if($this->user->wallet_amount < $request->amount) {
            return $this->error(null, "Insufficient wallet balance", 400);
        }

        $amount = $this->user->wallet_amount - $request->amount;

        $this->userDecrementBalance($this->user, $amount);
        $this->userIncrementBalance($user, $request->amount);

        Transaction::create([
            'user_id' => $this->user->id,
            'title' => 'Funds transfer',
            'amount' => $request->amount,
            'type' => 'DR',
            'receiver_id' => $user->id
        ]);

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

        return $this->success(null, "Transaction pin set successfully");
    }

    public function withdraw($request)
    {
        $user = User::with(['userPin', 'walletAccount', 'userWithdrawLogs', 'userBank'])
            ->findOrFail($request->user_id);

        if (!$user->userBank) {
            return $this->error(null, "No bank found", 404);
        }

        if (!$user->walletAccount) {
            return $this->error(null, "Wallet not found", 404);
        }

        if(empty($user?->userPin?->pin)) {
            return $this->error(null,  "Set your transaction pin!", 400);
        }

        if($request->amount > $user->earning_balance) {
            return $this->error(null, "Insufficient earning balance", 400);
        }

        DB::transaction(function () use ($user, $request) {
            $newBalance = $user->earning_balance - $request->amount;

            $user->userWithdrawLogs()->create([
                'amount' => $request->amount,
                'previous_balance' => $user->earning_balance,
                'new_balance' => $newBalance,
                'status' => General::PENDING,
                'ip_address' => $request->ip(),
                'device' => $request->header('User-Agent'),
            ]);

            $user->walletAccount()->update([
                'earnings' => $newBalance,
            ]);
        });

        return $this->success(null, "Request sent successfully");
    }

    public function balanceWithdraw($request)
    {
        $user = User::with(['userPin', 'walletAccount', 'userWithdrawLogs'])
            ->findOrFail($request->user_id);

        if (!$user->walletAccount) {
            return $this->error(null, "Wallet not found", 404);
        }

        if(empty($user?->userPin?->pin)) {
            return $this->error(null,  "Set your transaction pin!", 400);
        }

        if($request->amount > $user->earning_balance) {
            return $this->error(null, "Insufficient earning balance", 400);
        }

        $this->driverDecrementEarning($user, $request->amount);
        $this->userIncrementBalance($user, $request->amount);

        return $this->success(null, "Withdrawal to wallet successful");
    }

    public function recentTransaction($userId)
    {
        if ($this->user->id != $userId) {
            return $this->error(null, "Unauthorized action.", 401);
        }

        $date = request()->input('date');

        $user = User::with(['transactions' => function ($query) use ($date) {
            $query->when($date, fn($query) => $query->whereDate('created_at', $date));
        }])->findOrFail($userId);

        $relatedTransactions = Transaction::where('receiver_id', $userId)
            ->where('title', "Bus ticket purchase")
            ->select('id', 'user_id', 'title', 'amount', 'type', 'status', 'created_at')
            ->get();

        $transactions = $user->transactions->map(fn ($transaction) => [
            'id' => $transaction->id,
            'title' => $transaction->title,
            'amount' => (int)$transaction->amount,
            'type' => $transaction->type,
            'status' => $transaction->status,
            'created_at' => $transaction->created_at,
        ]);

        $relatedTransactions = $relatedTransactions->map(fn($transaction) => [
            'id' => $transaction->id,
            'title' => $transaction->title,
            'amount' => (int)$transaction->amount,
            'type' => $transaction->type,
            'status' => $transaction->status,
            'created_at' => $transaction->created_at,
        ]);

        $allTransactions = $transactions->merge($relatedTransactions);

        return $this->success($allTransactions, "Recent transactions");
    }

    public function recentEarning($userId)
    {
        if ($this->user->id != $userId) {
            return $this->error(null, "Unauthorized action.", 401);
        }

        $date = request()->input('date');

        $earnings = TripPayment::select('id', 'title', 'amount', 'status', 'created_at')
            ->where('driver_id', $userId)
            ->when($date, fn($query) => $query->whereDate('created_at', $date))
            ->get();

        return $this->success($earnings, "Recent earnings");
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
}
