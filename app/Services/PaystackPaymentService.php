<?php

namespace App\Services;

class PaystackPaymentService
{
    protected $paystack_secret_key;

    public function __construct(){
        $this->paystack_secret_key = config('app.paystack_secret_key');
    }

    public function intializeTransaction($request){

        $url = "https://api.paystack.co/transaction/initialize";

        $fields = [
            'email' => $request->email,
            'amount' => $request->amount * 100,
        ];

        $fields_string = http_build_query($fields);

        //open connection
        $ch = curl_init();

        //set the url, number of POST vars, POST data
        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_POST, true);
        curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Authorization: Bearer ".$this->paystack_secret_key,
            "Cache-Control: no-cache",
        ));

        //So that curl_exec returns the contents of the cURL; rather than echoing it
        curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);

        //execute post
        $result = curl_exec($ch);
        return json_decode($result);

    }

    public function verifyTransaction($transactionReference, $amount){

        $amount = $amount*100;

        $ch = curl_init();
        $url = 'https://api.paystack.co/transaction/verify/'.$transactionReference;

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer ".$this->paystack_secret_key,
            "Cache-Control: no-cache",
        ]);

        $response = json_decode(curl_exec($ch));
        if(isset($response->data)){

            if($response->data->status == 'success')
            {
                if($response->data->amount == $amount)
                return ['status' => $response->data->status];

                else return ['status' => 'failed', 'message' => 'Incorrect amount'];
            }

            return ['status' => $response->data->status, 'message' => $response->data->gateway_response];
        }
        return ['status' => $response->status, 'message' => $response->message];
    }
}
