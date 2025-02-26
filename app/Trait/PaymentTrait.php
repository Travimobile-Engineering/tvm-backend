<?php

namespace App\Trait;

use App\Enum\PaymentMethod;
use App\Enum\PaymentType;
use App\Enum\TripStatus;
use App\Models\Notification;
use App\Models\PremiumHireBooking;
use App\Models\Trip;
use App\Models\TripBooking;
use App\Models\User;
use App\Models\Vehicle\Vehicle;
use Illuminate\Support\Facades\DB;

trait PaymentTrait
{
    use HttpResponse, PaymentLogTrait;

    protected function handleFundWallet($event)
    {
        $paymentData = $event['data'];
        $userId = $paymentData['metadata']['user_id'];
        $amount = $paymentData['amount'];
        $formattedAmount = number_format($amount / 100, 2, '.', '');
        $ref = $paymentData['reference'];

        try {
            $user = User::with('transactions')->findOrFail($userId);

            DB::beginTransaction();

            $user->update([
                'wallet' => $user->wallet + $formattedAmount
            ]);

            $user->transactions()->create([
                'title' => PaymentType::FUND_WALLET,
                'amount' => $formattedAmount,
                'type' => PaymentType::CR,
                'txn_reference' => $ref
            ]);

            DB::commit();
            info("User with ID: {$user->id} topped up wallet with amount: {$formattedAmount}");
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    protected function handleTripBooking($event)
    {
        $paymentData = $event['data'];
        $userId = $paymentData['metadata']['user_id'];
        $tripId = $paymentData['metadata']['trip_id'];
        $type = PaymentType::TRIP_BOOKING;
        $user = User::with(['driverTripPayments', 'transactions', 'paymentLogs'])
            ->find($userId);

        if (!$user) {
            throw new \Exception("User with ID: {$userId} not found.");
        }

        $paymentLog = $this->logPayment($user, $event, $type, $tripId);

        try {
            DB::beginTransaction();

            $paymentData = $event['data'];
            $thirdPartyBooking = $paymentData['metadata']['third_party_booking'];
            $selectedSeat = $paymentData['metadata']['selected_seat'];
            $tripType = $paymentData['metadata']['trip_type'];
            $travellingWith = $paymentData['metadata']['travelling_with'];
            $thirdPartyPassenger = $paymentData['metadata']['third_party_passenger_details'];
            $paymentMethod = $paymentData['metadata']['payment_method'];
            $amount = $paymentData['amount'];
            $formattedAmount = number_format($amount / 100, 2, '.', '');
            $ref = $paymentData['reference'];
            $status = $paymentData['status'];

            $trip = Trip::with(
                [
                    'user.transitCompany',
                    'vehicle',
                    'tripBookings.user',
                    'departureRegion.state',
                    'destinationRegion.state',
                    'manifest'
                ]
            )
                ->findOrFail($tripId);

            $seats = $trip->vehicle?->seats;
            $destination = $trip->destinationRegion?->state?->name . ' > ' . $trip->destinationRegion?->name;

            $bookings = $trip->tripBookings()->where('status', 1)->get();

            $selected_seats = $bookings->pluck('selected_seat')->toArray();
            $available_seats = array_filter($seats, function ($seat) use ($selected_seats) {
                return !in_array($seat, $selected_seats);
            });

            $trip->available_seats = $available_seats;

            do {
                $booking_id = getRandomNumber();
            } while (TripBooking::where('booking_id', $booking_id)->exists());

            $payStatus = $status === "success" ? 1 : 0;

            TripBooking::create([
                'booking_id' => $booking_id,
                'payment_log_id' => $paymentLog->id,
                'trip_id' => $tripId,
                'user_id' => $user->id,
                'third_party_booking' => $thirdPartyBooking ?? 0,
                'selected_seat' => ucfirst($selectedSeat),
                'trip_type' => $tripType,
                'travelling_with' => $travellingWith ?? null,
                'third_party_passenger_details' => $thirdPartyPassenger ?? null,
                'amount_paid' => $formattedAmount ?? 0,
                'payment_method' => $paymentMethod ?? '',
                'payment_status' => $payStatus ?? 0,
            ]);

            Notification::create([
                'user_id' => $user->id,
                'title' => 'Booking Successful',
                'description' => 'Your bus ticket to ' . $destination . ' on ' . date("M jS Y h:iA", strtotime($trip->departure_at)) . ' has been successfully booked',
                'additional_data' => json_encode([
                    'booking_id' => $booking_id,
                    'note' => 'Please arrive atleast 30 minutes early to ensure a smooth boarding experience.',
                    'help_desk' => 'If you have any questions or need assistance, feel free to contact our support team.',
                ])
            ]);

            $user->driverTripPayments()->create([
                'user_id' => $user->id,
                'trip_id' => $tripId,
                'driver_id' => $trip->user_id,
                'amount' => $formattedAmount,
                'status' => 'pending'
            ]);

            $user->transactions()->create([
                'user_id' => $user->id,
                'title' => PaymentType::TRIP_BOOKING,
                'amount' => $formattedAmount,
                'type' => PaymentType::CR,
                'txn_reference' => $ref
            ]);

            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    protected function handlePremiumHire($event)
    {
        $paymentData = $event['data'];
        $vehicleId = $paymentData['metadata']['vehicle_id'];
        $userId = $paymentData['metadata']['user_id'];
        $vehicle =  Vehicle::with('user')->find($vehicleId);
        $type = PaymentType::PREMIUM_HIRE;
        $user = User::with([
                'transactions',
                'paymentLogs',
                'premiumHireBookings'
            ])
            ->find($userId);

        $paymentLog = $this->logPayment($user, $event, $type);

        try {
            DB::beginTransaction();

            $ticketType = $paymentData['metadata']['ticket_type'];
            $lng = $paymentData['metadata']['lng'];
            $lat = $paymentData['metadata']['lat'];
            $pickup_location = $paymentData['metadata']['pickup_location'];
            $dropoff_location = $paymentData['metadata']['dropoff_location'];
            $busStops = $paymentData['metadata']['bus_stops'];
            $luggage = $paymentData['metadata']['luggage'];
            $paymentType = $paymentData['metadata']['payment_type'];
            $time = $paymentData['metadata']['time'];
            $date = $paymentData['metadata']['date'];
            $amount = $paymentData['amount'];
            $formattedAmount = number_format($amount / 100, 2, '.', '');
            $ref = $paymentData['reference'];
            $status = $paymentData['status'];

            do {
                $uuid = getRandomNumber();
            } while (PremiumHireBooking::where('uuid', $uuid)->exists());

            $user->premiumHireBookings()->create([
                'user_id' => $userId,
                'uuid' => $uuid,
                'driver_id' => $vehicle->user_id,
                'vehicle_id' => $vehicleId,
                'payment_log_id' => $paymentLog->id,
                'ticket_type' => $ticketType,
                'lng' => $lng,
                'lat' => $lat,
                'pickup_location' => $pickup_location,
                'dropoff_location' => $dropoff_location,
                'bus_stops' => $busStops,
                'luggage' => $luggage,
                'amount' => $formattedAmount,
                'payment_type' => $paymentType,
                'payment_status' => $status,
                'payment_method' => PaymentMethod::PAYSTACK,
                'time' => $time,
                'date' => $date,
                'status' => TripStatus::UPCOMING,
            ]);

            Notification::create([
                'user_id' => $user->id,
                'title' => 'Booking Successful',
                'description' => 'Your premium hire booking has been successfully booked',
                'additional_data' => json_encode([
                    'booking_id' => $uuid,
                    'note' => 'Please arrive atleast 30 minutes early to ensure a smooth boarding experience.',
                    'help_desk' => 'If you have any questions or need assistance, feel free to contact our support team.',
                ])
            ]);

            $user->transactions()->create([
                'user_id' => $user->id,
                'title' => PaymentType::PREMIUM_HIRE,
                'amount' => $formattedAmount,
                'type' => PaymentType::CR,
                'txn_reference' => $ref
            ]);

            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }
}

