<?php

use App\Broadcasting\DriverBookingCancelledChannel;
use App\Broadcasting\PassengerStartTripChannel;
use App\Broadcasting\PassengerTripCancelledChannel;
use App\Models\Trip;
use App\Models\TripBooking;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::routes([
    'middleware' => ['auth:api'],
]);

Broadcast::channel('App.Models.User.{id}', fn (User $user, int $id): bool =>
    $user->id === $id
);

// Trip created notification
Broadcast::channel('trip.created.{userId}', fn (User $user, int $userId): bool =>
    $user->id === $userId
);

// Trip departure notification
Broadcast::channel('trip.departure.{tripId}', fn (User $user, int $tripId): bool =>
    $user->id === Trip::findOrFail($tripId)->user_id
);

// Start trip notification (Driver)
Broadcast::channel('trip.start.{tripId}', fn (User $user, int $tripId): bool =>
    $user->id === Trip::findOrFail($tripId)->user_id
);

// Start trip notification (Passenger)
Broadcast::channel('passenger.trip.start.{tripId}', PassengerStartTripChannel::class);

// Fund wallet notification
Broadcast::channel('wallet.funded.{userId}', fn (User $user, int $userId): bool =>
    $user->id === $userId
);

// Trip booking notification
Broadcast::channel('trip.booking.{userId}', fn (User $user, int $userId): bool =>
    $user->id === $userId
);

// Trip cancelled notification (Driver)
Broadcast::channel('trip.cancelled.{tripId}', fn (User $user, int $tripId): bool =>
    $user->id === Trip::findOrFail($tripId)->user_id
);

// Trip cancelled notification (Passenger)
Broadcast::channel('passenger.trip.cancelled.{tripId}', PassengerTripCancelledChannel::class);

// Booking cancelled notification (Passenger)
Broadcast::channel('booking.cancelled.{bookingId}', fn (User $user, string $bookingId): bool =>
    $user->id === TripBooking::where('booking_id', $bookingId)->user_id
);

// Booking cancelled notification (Driver)
Broadcast::channel('booking.cancelled.driver.{bookingId}', DriverBookingCancelledChannel::class);
