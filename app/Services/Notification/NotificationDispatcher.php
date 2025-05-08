<?php

namespace App\Services\Notification;

use App\DTO\NotificationDispatchData;
use App\Services\Notification\FirebaseService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class NotificationDispatcher
{
    public function __construct(
        protected FirebaseService $firebase
    ) {}

    public function send(NotificationDispatchData $dto): void
    {
        foreach ($dto->events as $event) {
            try {
                broadcast(new ($event['class'])(...$event['payload']));
            } catch (\Throwable $e) {
                Log::error("Broadcast failed for {$event['class']}: {$e->getMessage()}");
            }
        }

        $users = collect($dto->recipients instanceof Collection ? $dto->recipients : [$dto->recipients]);

        foreach ($users as $user) {
            $tokens = [];

            if (!empty($user->fcm_token)) {
                $tokens = [$user->fcm_token];
            }

            foreach ($tokens as $token) {
                try {
                    $this->firebase->sendToToken(
                        $token,
                        $dto->title ?? 'Notification',
                        $dto->body ?? 'You have a new notification.',
                        array_merge($dto->data, ['user_id' => $user->id])
                    );
                } catch (\Throwable $e) {
                    Log::error("FCM error for user {$user->id}: {$e->getMessage()}");
                }
            }
        }
    }

}


