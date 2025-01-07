<?php

namespace App\Services;

use App\Models\User;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Mail\ConfirmationEmail;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Http\Requests\FundWalletRequest;
use App\Http\Requests\WalletTransferRequest;
use Illuminate\Validation\ValidationException;
use App\Http\Requests\WalletSetTransactionPinRequest;
use App\Http\Controllers\Payment\PaystackPaymentController;

class WalletService
{
    protected $user; 

    public function __construct(){
        $this->user = JWTAuth::user();
    }

    public function getBalance(){
        return ['data' => $this->user->wallet];
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
        $transactions = Transaction::where('user_id', $this->user->id)->get();
        return ['data' => $transactions];
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
}
