<?php

namespace App\DTO;

use App\Models\User;
use Illuminate\Support\Collection;

class NotificationDispatchData
{
    public function __construct(
        public string $eventClass,
        public array $eventPayload,
        public User|Collection $recipients,
        public ?string $title = null,
        public ?string $body = null,
        public array $data = []
    ) {}
}



