<?php

namespace App\Services;

use App\Enum\PaymentType;
use App\Events\WalletFunded;
use App\Models\Bank;
use App\Models\User;
use App\Models\Transaction;
use App\Trait\HttpResponse;
use Illuminate\Support\Str;
use App\Mail\ConfirmationEmail;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Services\Paystack\PaystackService;
use App\Http\Controllers\Payment\PaystackPaymentController;
use App\Mail\VerifyPinMail;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Unicodeveloper\Paystack\Facades\Paystack;

class WalletService
{
    use HttpResponse;

    protected $user;

    public function __construct(){
        $this->user = JWTAuth::user();
    }

    public function getBalance()
    {
        $userId = request()->input('userId') ?? $this->user->id;

        $user = User::find($userId);

        if (!$user) {
            return $this->error("User not found", 404);
        }

        return $this->success(['data' => $user->wallet ?? []], "Wallet balance retrieved");
    }

    public function fundWallet($request){

        $ppc = new PaystackPaymentController();

        $response = $ppc->verifyTransaction($request->reference, $request->amount);

        if($response['status'] == 'success'){

            $user = User::where('id', $this->user->id)->update(['wallet' => $this->user->wallet + $request->amount]);

            if($user){

                Transaction::create([
                    'user_id' => $this->user->id,
                    'title' => 'Wallet top up',
                    'amount' => $request->amount,
                    'type' => 'CR',
                    'txn_reference' => $request->reference
                ]);

                broadcast(new WalletFunded($user, $request->amount));

                return ['message' => 'Wallet funded successfully', 'data' => User::find($this->user->id)];
            }
        }

        else return ['message' => $response, 'code' => 400];
    }

    public function transfer($request){

        if(!in_array(2, json_decode($this->user->user_category)))
        return ['message'=>'You can only make transfers to agents', 'code' => 400];


        if($this->user->txn_pin != $request->pin) return ['message' => 'Incorrect transaction pin', 'code' => 400];
        if($this->user->wallet < $request->amount) return ['message' => 'Your balance is insufficient to complete this transaction. Please fund your wallet first', 'code' =>400];
        if(!User::where('agent_id', $request->agent_id)->exists() || $request->agent_id == $this->user->agent_id) return ['message' => 'Invalid agent ID', 'code' => 400];

        $this->user->update(['wallet' => $this->user->wallet - $request->amount]);
        $receiver = User::where('agent_id', $request->agent_id)->first();
        $status = $receiver->update(['wallet' => $receiver->wallet + $request->amount]);

        if($status)
        {
            Transaction::create([
                'user_id' => $this->user->id,
                'title' => 'Funds transfer',
                'amount' => $request->amount,
                'type' => 'DR',
                'receiver_id' => $receiver->id
            ]);

            return ['message' => 'Funds tranfered successfully'];
        }
        return ['message' => 'Please try again. Something went wrong', 'code' => 400];

    }

    public function getTransactions(){
        $userId = request()->input('userId') ?? $this->user->id;

        $user = User::with('transactions')->find($userId);

        if (!$user) {
            return $this->error("User not found", 404);
        }

        return $this->success(['data' => $user->transactions ?? []], "Wallet transactions retrieved");
    }

    public function setTransactionPin($request){

        if($this->user->txn_pin > 0){

            if(!isset($request->verification_code)){

            //Pin has already been set. Send OTP
            $verification_code = generateVerificationCode();
            $now = Carbon::now();
            $verification_code_expires_at = $now->addMinutes(10);

            User::where('id', Auth::id())
            ->update([
                'verification_code' => $verification_code,
                'verification_code_expires_at' => $verification_code_expires_at
            ]);

            Mail::to($this->user->email)->send(new ConfirmationEmail($this->user->first_name." ".$this->user->last_name, $verification_code, 'email.change_transaction_pin_otp'));
            return ['message' => 'Verification OTP has been sent to your email address'];
            }

            elseif(
                $request->verification_code != $this->user->verification_code
                || Carbon::now() > $this->user->verification_code_expires_at
            ) return ['message' => 'Invalid or expired verification code'];
        }

        $user = User::where('id', $this->user->id)->update([
            'txn_pin' => $request->pin,
            'verification_code' => ''
        ]);
        if($user) return ['message' => 'Transaction pin updated successfully'];
    }

    public function getTransactionPin(){
        $pin = User::where('id', $this->user->id)->pluck('txn_pin')->first();
        return ['data' => $pin];
    }

    public function walletSetup($request)
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

                    sendMail($user->email, new VerifyPinMail($user->first_name, $code));

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

            sendMail($user->email, new VerifyPinMail($user->first_name, $code));

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

    public function withdraw($request)
    {
        $user = User::with(['userPin', 'userTransferReceipient', 'userWithdrawLogs'])
            ->findOrFail($request->user_id);

        if($user->wallet <= 0) {
            return $this->error(null, "Insufficient wallet balance", 400);
        }

        if($request->amount > $user->wallet) {
            return $this->error(null, "Insufficient wallet balance", 400);
        }

        if(empty($user?->userPin?->pin)){
            return $this->error(null,  "Set your transaction pin!", 400);
        }

        $fields = [
            "source" => "balance",
            "reason" => "Withdrawal",
            "amount" => $request->amount . 00,
            "reference" => Str::uuid(),
            "recipient" => $user->userTransferReceipient?->recipient_code,
        ];

        return PaystackService::transfer($user, $fields);
    }

    public function recentTransaction($userId)
    {
        if ($this->user->id != $userId) {
            return $this->error(null, "Unauthorized action.", 401);
        }

        $date = request()->input('date');

        $user = User::with(['transactions' => function ($query) use ($date) {
            if ($date) {
                $query->whereDate('created_at', $date);
            }
        }])->findOrFail($userId);

        $relatedTransactions = Transaction::where('receiver_id', $userId)
            ->where('title', "Bus ticket purchase")
            ->select('id', 'user_id', 'title', 'amount', 'status', 'created_at')
            ->get();

        $transactions = $user->transactions->map(function ($transaction) {
            return [
                'id' => $transaction->id,
                'title' => $transaction->title,
                'amount' => (int)$transaction->amount,
                'status' => $transaction->status,
                'created_at' => $transaction->created_at,
            ];
        });

        $relatedTransactions = $relatedTransactions->map(function ($transaction) {
            return [
                'id' => $transaction->id,
                'title' => $transaction->title,
                'amount' => (int)$transaction->amount,
                'status' => $transaction->status,
                'created_at' => $transaction->created_at,
            ];
        });

        $allTransactions = $transactions->merge($relatedTransactions);

        return $this->success($allTransactions, "Recent transactions");
    }

    public function recentEarning($userId)
    {
        if ($this->user->id != $userId) {
            return $this->error(null, "Unauthorized action.", 401);
        }

        $date = request()->input('date');

        $user = User::with(['driverTripPayments' => function ($query) use ($date) {
            if ($date) {
                $query->whereDate('created_at', $date);
            }
        }])->findOrFail($userId);

        $earnings = $user->driverTripPayments->map(function ($payment) {
            return [
                'id' => $payment->id,
                'amount' => (int)$payment->amount,
                'status' => $payment->status,
                'created_at' => $payment->created_at,
            ];
        });

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
            'email' => $request->input('email'),
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
        $startDate = request()->input('start_date') ?: now()->startOfWeek();
        $endDate = request()->input('end_date') ?: now()->endOfWeek();

        $allDays = [
            'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'
        ];

        $transactions = Transaction::where('user_id', $userId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw("
                DAYNAME(created_at) as day_of_week,
                SUM(CASE WHEN type = 'CR' THEN amount ELSE 0 END) as inflow,
                SUM(CASE WHEN type = 'DR' THEN amount ELSE 0 END) as outflow
            ")
            ->groupBy('day_of_week')
            ->get()
            ->keyBy('day_of_week');

        $statistics = collect($allDays)->map(function ($day) use ($transactions) {
            return [
                'day' => $day,
                'inflow' => (int) ($transactions[$day]->inflow ?? 0),
                'outflow' => (int) ($transactions[$day]->outflow ?? 0),
            ];
        });

        return $this->success($statistics, "Transaction statistics retrieved successfully.");
    }
}
