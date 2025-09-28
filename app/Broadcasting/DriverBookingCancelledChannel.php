<?php

namespace App\Broadcasting;

use App\Models\Trip;
use App\Models\TripBooking;
use App\Models\User;

class DriverBookingCancelledChannel
{
    /**
     * Create a new channel instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Authenticate the user's access to the channel.
     */
    public function join(User $user, $bookingId): array|bool
    {
        $tripId = TripBooking::where('booking_id', $bookingId)->value('trip_id');

        if (! $tripId) {
            return false;
        }

        $tripOwnerId = Trip::where('id', $tripId)->value('user_id');

        return $user->id === $tripOwnerId;
    }
}
