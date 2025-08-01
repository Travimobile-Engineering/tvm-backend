<?php

namespace App\Observers;

use App\Contracts\SMS;
use App\Models\TripBooking;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Support\Str;

class TripBookingObserver implements ShouldHandleEventsAfterCommit
{
    /**
     * Handle the TripBooking "created" event.
     */
    public function created(TripBooking $tripBooking): void
    {
        $smsService = app(SMS::class);
        $user = $tripBooking->user;
        $trip = $tripBooking->trip;

        if (!$user || !$trip || !$user->phone_number) {
            return;
        }

        // $trip->load(['departureRegion', 'destinationRegion']);

        // $name = Str::limit($user->first_name, 15, '');
        // $from = Str::limit($trip->departureRegion?->name, 12, '');
        // $to = Str::limit($trip->destinationRegion?->name, 12, '');
        // $duration = $trip->trip_duration ?? 'N/A';
        // $amount = number_format($tripBooking->amount_paid ?? 0, 2);
        // $bookingId = $tripBooking->booking_id;

        // $message = "Hi $name, your trip from $from to $to is booked. Booking ID: $bookingId. Duration: $duration. Amount paid: ₦$amount. Powered by Travi.";

        // $smsService->sendSms(
        //     formatPhoneNumber($user->phone_number),
        //     $message
        // );
    }

    /**
     * Handle the TripBooking "updated" event.
     */
    public function updated(TripBooking $tripBooking): void
    {
        //
    }

    /**
     * Handle the TripBooking "deleted" event.
     */
    public function deleted(TripBooking $tripBooking): void
    {
        //
    }

    /**
     * Handle the TripBooking "restored" event.
     */
    public function restored(TripBooking $tripBooking): void
    {
        //
    }

    /**
     * Handle the TripBooking "force deleted" event.
     */
    public function forceDeleted(TripBooking $tripBooking): void
    {
        //
    }
}
