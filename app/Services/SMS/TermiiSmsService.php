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
        $channel = $this->determineSmsChannel($to);
        return [
            'to' => $to,
            'from' => $channel === 'dnd' ? config('services.termii.sender_id_default')
                : config('services.termii.sender_id'),
            'sms' => $message,
            'type' => 'plain',
            'channel' => $channel,
            'api_key' => config('services.termii.api_key')
        ];
    }

    protected function determineSmsChannel($to): string
    {
        if (is_array($to)) {
            return 'generic';
        } else {
            $phone_check = (new PhoneNumberCheckService($to))->run();
            if (isset($phone_check['status']) && $phone_check['status'] === 'DND blacklisted') {
                return 'dnd';
            } elseif ($phone_check['message'] && $phone_check['message'] === self::NO_RECORD) {
                return 'dnd';
            } else {
                return 'generic';
            }
        }
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


