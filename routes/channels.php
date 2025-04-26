<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', fn (User $user, int $id): bool =>
    $user->id === $id
);

Broadcast::channel('trip.created.{id}', fn (User $user, int $id): bool =>
    $user->id === $id
);

Broadcast::channel('trip.departure.{tripId}', fn (User $user, int $tripId): bool =>
    $user->trips()->where('id', $tripId)->exists()
);

Broadcast::channel('trip.start.{tripId}', fn (User $user, int $tripId): bool =>
    $user->trips()->where('id', $tripId)->exists()
);

Broadcast::channel('wallet.funded.{userId}', fn (User $user, int $userId): bool =>
    $user->id === $userId
);

Broadcast::channel('trip.booking.{userId}', fn (User $user, int $userId): bool =>
    $user->id === $userId
);

Broadcast::channel('trip.cancelled.{tripId}', fn (User $user, int $tripId): bool =>
    $user->trips()->where('id', $tripId)->exists()
);

Broadcast::channel('booking.cancelled.{bookingId}', fn (User $user, int $bookingId): bool =>
    $user->bookings()->where('id', $bookingId)->exists()
);
