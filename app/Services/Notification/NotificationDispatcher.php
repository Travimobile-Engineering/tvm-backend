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
        try {
            broadcast(new ($dto->eventClass)(...$dto->eventPayload));
        } catch (\Throwable $e) {
            Log::error("Broadcast failed: {$e->getMessage()}");
        }

        $users = collect($dto->recipients instanceof Collection ? $dto->recipients : [$dto->recipients]);

        foreach ($users as $user) {
            $tokens = [];

            if (method_exists($user, 'deviceTokens')) {
                $tokens = $user->deviceTokens()->pluck('token')->all();
            } elseif (!empty($user->fcm_token)) {
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


