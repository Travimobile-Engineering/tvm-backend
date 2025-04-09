<?php

namespace App\Services\SMS;

use App\Contracts\SMS;
use App\Enum\SmsProvider;

class TermiiSmsService implements SMS
{
    public const NO_RECORD = 'Phone either not on DND or is not in our Database';

    public function sendSms(string|array $to, string $message): array
    {
        $curl = curl_init();
        $data = $this->getData($to, $message);
        $post_data = json_encode($data);

        try {
            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://v3.api.termii.com/api/sms/send",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => $post_data,
                CURLOPT_HTTPHEADER => array(
                "Content-Type: application/json"
                ),
            ));

            $response = curl_exec($curl);
            $this->logResponse($to, $message, $response, 'success');
            $decoded = json_decode($response, true);

            return is_array($decoded)
                ? $decoded
                : [
                    'status' => false,
                    'message' => 'Invalid JSON response from Termii',
                    'data' => $response
                ];
        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => $e->getMessage(),
                'data'=> null
            ];
        }
    }

    protected function getData($to, $message): array
    {
        $channel = $this->determineSmsChannel();
        return [
            'to' => $to,
            'from' => config('services.termii.sender_id_default'),
            'sms' => $message,
            'type' => 'plain',
            'channel' => $channel,
            'api_key' => config('services.termii.api_key')
        ];
    }

    protected function determineSmsChannel(): string
    {
        return 'dnd';
    }

    protected function logResponse($to, $message, $response, $status)
    {
        return (new LogService(
            $to,
            $this->getData($to, $message),
            $response,
            SmsProvider::TERMII,
            $status
        ))->run();
    }
}


