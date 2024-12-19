<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Payment\PaystackPaymentController;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Tymon\JWTAuth\Facades\JWTAuth;

class WalletController extends Controller
{

    protected $user; 

    public function __construct(){
        $this->user = JWTAuth::user();
    }

    public function getBalance(){
        return response()->json(['data' => $this->user->wallet]);
    }

    public function fundWallet(Request $request){

        try{
            $request->validate([
                'reference' => 'required|string',
                'amount' => 'required|int'
            ]);
        }
        catch(ValidationException $e){
            return response()->json(['error' => collect($e->errors())->flatten()->first()], 400);
        }
        
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
                return response()->json(['message' => 'Wallet funded successfully', 'data' => User::find($this->user->id)], 200);
            }
        }

        else return response()->json($response, 400);
        
    }

    public function transfer(Request $request){
        
        try
        {
            $request->validate([
                'email' => 'required|email',
                'amount' => 'required|int'
            ]);
        }
        catch(ValidationException $e){
            return response()->json(['error' => collect($e->errors())->flatten()->first()], 400);
        }

        if($this->user->wallet < $request->amount) return response()->json(['error' => 'Your balance is insufficient to complete this transaction. Please fund your wallet first'], 400);
        if(!User::where('email', $request->email)->exists() || $request->email == $this->user->email) return response()->json(['error' => 'Invalid receiver email address'], 400);

        $this->user->update(['wallet' => $this->user->wallet - $request->amount]);
        $receiver = User::where('email', $request->email)->first();
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

            return response()->json(['message' => 'Funds tranfered successfully'], 200);
        }
        return response()->json(['error' => 'Please try again. Something went wrong'], 400);
        
    }

    public function getTransactions(){
        $transactions = Transaction::where('user_id', $this->user->id)->get();
        return response()->json(['data' => $transactions]);
    }

    public function setTransactionPin(Request $request){
        try{
            $request->validate([
                'pin' => 'required|digits:4',
            ]);
        }catch(ValidationException $e){
            return response()->json(['error' => collect($e->errors())->flatten()->first()], 400);
        }

        $user = User::where('id', $this->user->id)->update(['txn_pin' => $request->pin]);
        if($user) return response()->json(['message' => 'Transaction pin updated successfully'], 200);
    }

    public function getTransactionPin(){
        $pin = User::where('id', $this->user->id)->pluck('txn_pin')->first();
        return response()->json(['data' => $pin], 200);
    }
}
