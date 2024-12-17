<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Payment\PaystackPaymentController;
use App\Models\User;
use Illuminate\Http\Request;
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
            return response()->json(['error' => collect($e->errors())->flatten()->first()]);
        }
        
        $ppc = new PaystackPaymentController();

        $response = $ppc->verifyTransaction($request->reference, $request->amount);

        if($response['status'] == 'success'){
            $user = User::where('id', $this->user->id)->update(['wallet' => $this->user->wallet + $request->amount]);
            if($user){
                return response()->json(['message' => 'Wallet funded successfully', 'data' => User::find($this->user->id)]);
            }
        }

        else return response()->json($response);
        
    }
}
