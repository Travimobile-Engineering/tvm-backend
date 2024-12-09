<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PaystackPaymentController extends Controller
{
    public function intializeTransaction(Request $request){
        
        try{

            $request->validate([
                'email' => 'required|email',
                'amount' => 'required|integer',
            ]);
        }
        catch(ValidationException $e){
            return response()->json(['error' => collect($e->errors())->flatten()->first()]);
        }

        $url = "https://api.paystack.co/transaction/initialize";
        $paystack_secret_key = 'sk_test_5f66ae04f0233009da14af3422e0fdf781a7a90d';

        $fields = [
            'email' => $request->email,
            'amount' => $request->amount,
        ];

        $fields_string = http_build_query($fields);

        //open connection
        $ch = curl_init();
        
        //set the url, number of POST vars, POST data
        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_POST, true);
        curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Authorization: Bearer ".$paystack_secret_key,
            "Cache-Control: no-cache",
        ));
        
        //So that curl_exec returns the contents of the cURL; rather than echoing it
        curl_setopt($ch,CURLOPT_RETURNTRANSFER, true); 
        
        //execute post
        $result = curl_exec($ch);
        return response(['data' => $result]);

    }
}
