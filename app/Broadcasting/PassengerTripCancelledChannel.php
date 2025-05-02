<?php

namespace App\Broadcasting;

use App\Models\TripBooking;
use App\Models\User;

class PassengerTripCancelledChannel
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
    public function join(User $user, int $tripId): array|bool
    {
        return TripBooking::where('trip_id', $tripId)
            ->where('user_id', $user->id)
            ->exists();
    }
}
