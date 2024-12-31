<?php

namespace App\Services;

use App\Http\Controllers\Payment\PaystackPaymentController;
use App\Http\Requests\FundWalletRequest;
use App\Http\Requests\WalletSetTransactionPinRequest;
use App\Http\Requests\WalletTransferRequest;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Tymon\JWTAuth\Facades\JWTAuth;

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

    public function setTransactionPin(WalletSetTransactionPinRequest $request){

        $user = User::where('id', $this->user->id)->update(['txn_pin' => $request->pin]);
        if($user) return ['message' => 'Transaction pin updated successfully'];
    }

    public function getTransactionPin(){
        $pin = User::where('id', $this->user->id)->pluck('txn_pin')->first();
        return ['data' => $pin];
    }
}
