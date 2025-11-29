<?php

namespace App\Services\SMS;

use App\Contracts\SMS;
use Illuminate\Support\Facades\Http;

class AfricasTalkingSmsService implements SMS
{
    protected string $username;

    protected string $apiKey;

    protected string $senderId;

    protected string $url;

    public function __construct()
    {
        $this->username = config('services.africastalking.username');
        $this->apiKey = config('services.africastalking.api_key');
        $this->senderId = config('services.africastalking.senderId');
        $this->url = config('services.africastalking.url');
    }

    public function sendSms(string|array $to, string $message): array
    {
        $recipients = is_array($to) ? $to : [$to];

        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'apiKey' => $this->apiKey,
        ])->post($this->url, [
            'username' => $this->username,
            'message' => $message,
            'senderId' => $this->senderId,
            'phoneNumbers' => $recipients,
        ]);

        return $response->json();
    }
}
