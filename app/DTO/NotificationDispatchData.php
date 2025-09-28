<?php

namespace App\DTO;

use App\Models\User;
use Illuminate\Support\Collection;

class NotificationDispatchData
{
    /**
     * @param  array<array{class: string, payload: array}>  $events
     */
    public function __construct(
        public array $events,
        public User|Collection $recipients,
        public ?string $title = null,
        public ?string $body = null,
        public array $data = []
    ) {}
}
