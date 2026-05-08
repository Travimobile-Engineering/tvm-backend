<?php

namespace App\Services\Notification;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class FirebaseService
{
    protected $messaging = null;

    protected function messaging()
    {
        if ($this->messaging === null) {
            $credentials = config('services.firebase.credentials');

            $factory = (new Factory)->withServiceAccount($credentials);
            $this->messaging = $factory->createMessaging();
        }

        return $this->messaging;
    }

    public function sendToToken(string $deviceToken, string $title, string $body, array $data = [])
    {
        $notification = Notification::create($title, $body);

        // FCM requires all data payload values to be strings
        $data = array_map('strval', $data);

        $message = CloudMessage::fromArray(['token' => $deviceToken])
            ->withNotification($notification)
            ->withData($data);

        return $this->messaging()->send($message);
    }
}
