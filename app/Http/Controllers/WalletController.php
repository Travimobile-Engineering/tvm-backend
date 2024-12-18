<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Payment\PaystackPaymentController;
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

        if($status) return response()->json(['message' => 'Funds tranfered successfully'], 200);
        return response()->json(['error' => 'Please try again. Something went wrong'], 400);
        
    }
}
