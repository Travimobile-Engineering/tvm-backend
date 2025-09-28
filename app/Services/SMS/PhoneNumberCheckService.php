<?php

namespace App\Services\SMS;

class PhoneNumberCheckService
{
    public function __construct(
        protected $phone_number
    ) {}

    public function run()
    {
        try {
            $curl = curl_init();
            $data = [
                'api_key' => config('services.termii.api_key'),
                'phone_number' => $this->phone_number,
            ];
            $post_data = json_encode($data);
            curl_setopt_array($curl, [
                CURLOPT_URL => 'https://termii.com/api/check/dnd',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_POSTFIELDS => $post_data,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                ],
            ]);
            $response = curl_exec($curl);
            curl_close($curl);

            return json_decode($response, true);
        } catch (\Exception $exception) {
            return $exception->getMessage();
        }
    }
}
