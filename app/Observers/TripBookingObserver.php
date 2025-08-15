<?php

namespace App\Observers;

use App\Contracts\SMS;
use App\Models\TripBooking;
use Illuminate\Support\Str;
use App\Services\ERP\ChargeService;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class TripBookingObserver implements ShouldHandleEventsAfterCommit
{
    /**
     * Handle the TripBooking "created" event.
     */
    public function created(TripBooking $tripBooking): void
    {
        if (! app()->environment('production')) {
            return;
        }

        $user = $tripBooking->user;

        if (! $user || ! $user->phone_number) {
            return;
        }

        $trip = $tripBooking->trip;

        if (! $trip) {
            return;
        }

        $trip->loadMissing(['departureRegion', 'destinationRegion']);

        $from = $trip->departureRegion?->name;
        $to = $trip->destinationRegion?->name;

        if (! $from || ! $to) {
            return;
        }

        $name = Str::limit($user->first_name ?? 'User', 15);
        $from = Str::limit($from, 12);
        $to = Str::limit($to, 12);
        $duration = $trip->trip_duration ?? 'N/A';
        $amount = number_format($tripBooking->amount_paid ?? 0, 2);
        $bookingId = $tripBooking->booking_id ?? 'N/A';

        $message = "Hi $name, your trip from $from to $to is booked. Booking ID: $bookingId. Duration: $duration. Amount paid: ₦$amount. Powered by Travi.";

        if ($user->inbox_notifications) {
            // Send SMS
            app(SMS::class)->sendSms(
                formatPhoneNumber($user->phone_number),
                $message
            );

            // Charge user for SMS
            app(ChargeService::class)->smsCharge($user);
        }

        // Admin Charge
        app(ChargeService::class)->adminCharge($user);

        // VAT Charge
        app(ChargeService::class)->vatCharge($user);
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
