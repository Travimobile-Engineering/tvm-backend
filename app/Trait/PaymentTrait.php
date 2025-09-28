<?php

namespace App\Trait;

use App\DTO\NotificationDispatchData;
use App\Enum\PaymentMethod;
use App\Enum\PaymentStatus;
use App\Enum\PaymentType;
use App\Enum\TransactionTitle;
use App\Enum\TripStatus;
use App\Models\Notification;
use App\Models\PaymentLog;
use App\Models\PremiumHireBooking;
use App\Models\Trip;
use App\Models\TripBooking;
use App\Models\User;
use App\Models\Vehicle\Vehicle;
use App\Services\ERP\ChargeService;
use App\Services\Notification\NotificationDispatcher;
use Illuminate\Support\Facades\DB;

trait PaymentTrait
{
    use DriverTrait, HttpResponse, PaymentLogTrait;

    protected NotificationDispatcher $notifier;

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

            $this->userIncrementBalance($user, $formattedAmount);

            $user->createTransaction(
                TransactionTitle::CREDIT_WALLET->value,
                $formattedAmount,
                PaymentType::CR,
                $ref,
                null,
                'Wallet funded successfully'
            );

            DB::commit();

            $this->notifier->send(new NotificationDispatchData(
                events: [],
                recipients: $user,
                title: 'Wallet Funded',
                body: "₦{$formattedAmount} has been added to your wallet.",
                data: [
                    'type' => 'wallet_funded',
                    'amount' => $formattedAmount,
                ]
            ));

            info("User with ID: {$user->id} topped up wallet with amount: {$formattedAmount}");
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    protected function handleTripBooking($event): void
    {
        $paymentData = $event['data'];
        $user = $this->getUserWithRelations($paymentData['metadata']['user_id']);
        $trip = $this->getTripWithRelations($paymentData['metadata']['trip_id']);
        $paymentLog = $this->logPayment($user, $event, PaymentType::TRIP_BOOKING, $trip->id);

        DB::beginTransaction();

        try {
            $bookingDetails = $this->prepareBookingData($paymentData, $user);
            $bookingId = $this->generateUniqueBookingId();
            $tripBooking = $this->storeTripBooking($bookingId, $trip, $user, $paymentLog, $bookingDetails);

            if ((int) $bookingDetails['third_party_booking'] === 0) {
                $this->storeTripPassengers(
                    $tripBooking,
                    $bookingDetails['passengers'],
                    $user,
                    $bookingDetails['third_party_passenger_details']
                );
            }

            $this->notifyUserBooking($user, $trip, $bookingId);
            $this->recordTransactions($trip, $user, $paymentData, $bookingId);

            DB::commit();

            $this->dispatchTripBookedEvent($user);
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    protected function handlePremiumHire($event)
    {
        try {
            $paymentData = $event['data'];
            $vehicleId = $paymentData['metadata']['vehicle_id'];
            $userId = $paymentData['metadata']['user_id'];
            $vehicle = Vehicle::with('user')->find($vehicleId);
            $type = PaymentType::PREMIUM_HIRE;
            $user = User::with([
                'transactions',
                'paymentLogs',
                'premiumHireBookings',
            ])
                ->find($userId);

            $paymentLog = $this->logPayment($user, $event, $type);

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
                'bus_stops' => $busStops ?? [],
                'luggage' => $luggage ?? [],
                'amount' => $formattedAmount,
                'payment_type' => $paymentType,
                'payment_status' => $status,
                'payment_method' => PaymentMethod::PAYSTACK,
                'time' => $time,
                'date' => $date,
                'status' => TripStatus::REQUEST,
            ]);

            Notification::create([
                'user_id' => $user->id,
                'title' => 'Booking Successful',
                'description' => 'Your premium hire booking has been successfully booked',
                'additional_data' => json_encode([
                    'booking_id' => $uuid,
                    'note' => 'Please arrive atleast 30 minutes early to ensure a smooth boarding experience.',
                    'help_desk' => 'If you have any questions or need assistance, feel free to contact our support team.',
                ]),
            ]);

            $user->transactions()->create([
                'user_id' => $user->id,
                'title' => TransactionTitle::PREMIUM_HIRE->value,
                'amount' => $formattedAmount,
                'type' => PaymentType::CR,
                'txn_reference' => $ref,
            ]);

            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    protected function isAlreadyProcessed($event)
    {
        $paymentData = $event['data'];
        $tripId = $paymentData['metadata']['trip_id'];
        $userId = $paymentData['metadata']['user_id'];
        $selectedSeats = explode(',', str_replace(' ', '', $paymentData['metadata']['selected_seat']));

        $paymentLogExists = PaymentLog::where([
            'trip_id' => $tripId,
            'user_id' => $userId,
            'type' => PaymentType::TRIP_BOOKING,
        ])
            ->whereIn('channel', ['card', 'bank', 'ussd', 'qr', 'mobile_money', 'bank_transfer'])
            ->exists();

        $tripBookingExists = TripBooking::where([
            'trip_id' => $tripId,
            'user_id' => $userId,
            'payment_status' => 1,
            'payment_method' => PaymentType::TRIP_BOOKING,
        ])
            ->where(function ($query) use ($selectedSeats) {
                foreach ($selectedSeats as $seat) {
                    $query->orWhereJsonContains('selected_seat', $seat);
                }
            })
            ->exists();

        return $paymentLogExists || $tripBookingExists;
    }

    /**
     * Get user with payment-related relationships loaded.
     */
    private function getUserWithRelations(int $userId): User
    {
        return User::with(['driverTripPayments', 'transactions', 'paymentLogs'])->findOrFail($userId);
    }

    private function getTripWithRelations($tripId): Trip
    {
        return Trip::with([
            'user.transitCompany',
            'user.walletAccount',
            'vehicle',
            'tripBookings.user',
            'departureRegion.state',
            'destinationRegion.state',
            'manifest',
        ])->findOrFail($tripId);
    }

    private function prepareBookingData(array $paymentData, User $user): array
    {
        $meta = $paymentData['metadata'];

        $selectedSeats = explode(',', str_replace(' ', '', $meta['selected_seat']));
        $amount = number_format($paymentData['amount'] / 100, 2, '.', '');
        $payStatus = $paymentData['status'] === 'success' ? 1 : 0;

        $travellingWith = collect($meta['travelling_with'] ?? [])->filter(fn ($p) => ! empty($p['name']) || ! empty($p['email']) || ! empty($p['phone_number']) || ! empty($p['gender'])
        )->values();

        if ($travellingWith->isEmpty()) {
            $travellingWith = null;
        }

        $passengers = collect($travellingWith ?? []);
        $passengers->prepend([
            'name' => $user->first_name.' '.$user->last_name,
            'email' => $user->email,
            'phone_number' => $user->phone_number,
            'gender' => $user->gender ?? 'unknown',
        ]);

        return [
            'booking_status' => $payStatus,
            'selected_seat' => $selectedSeats,
            'trip_type' => $meta['trip_type'] ?? null,
            'third_party_booking' => $meta['third_party_booking'] ?? 0,
            'payment_method' => $meta['payment_method'] ?? '',
            'third_party_passenger_details' => $meta['third_party_passenger_details'] ?? [],
            'amount_paid' => $amount,
            'passengers' => $passengers,
            'raw_travelling_with' => $travellingWith,
            'charges' => $meta['charges'] ?? null,
        ];
    }

    private function generateUniqueBookingId(): string
    {
        do {
            $bookingId = getRandomNumber();
        } while (TripBooking::where('booking_id', $bookingId)->exists());

        return $bookingId;
    }

    private function storeTripBooking(string $bookingId, Trip $trip, User $user, $paymentLog, array $details): TripBooking
    {
        return TripBooking::create([
            'booking_id' => $bookingId,
            'payment_log_id' => $paymentLog->id,
            'trip_id' => $trip->id,
            'user_id' => $user->id,
            'third_party_booking' => $details['third_party_booking'],
            'selected_seat' => $details['selected_seat'],
            'trip_type' => $details['trip_type'],
            'travelling_with' => $details['third_party_booking'] === 0 ? $details['raw_travelling_with'] : null,
            'third_party_passenger_details' => $details['third_party_passenger_details'] ?? null,
            'amount_paid' => $details['amount_paid'],
            'payment_method' => $details['payment_method'],
            'payment_status' => $details['booking_status'],
            'receive_sms' => 0,
        ]);
    }

    private function storeTripPassengers(TripBooking $booking, $passengers, User $user, array $thirdParty): void
    {
        foreach ($passengers as $index => $p) {
            $booking->tripBookingPassengers()->create([
                'trip_booking_id' => $booking->id,
                'name' => $p['name'],
                'email' => $p['email'] ?? null,
                'phone_number' => $p['phone_number'],
                'gender' => $p['gender'] ?? 'unknown',
                'selected_seat' => $booking->selected_seat[$index] ?? null,
                'on_seat' => false,
                'next_of_kin' => $index === 0 ? ($user->next_of_kin ?? '') : ($thirdParty[$index - 1]['name'] ?? ''),
                'next_of_kin_phone_number' => $index === 0 ? ($user->next_of_kin_phone ?? '') : ($thirdParty[$index - 1]['phone_number'] ?? ''),
            ]);
        }
    }

    private function notifyUserBooking(User $user, Trip $trip, string $bookingId): void
    {
        $destination = "{$trip->destinationRegion?->state?->name} > {$trip->destinationRegion?->name}";
        $date = \Carbon\Carbon::parse($trip->departure_date)->format('Y-m-d');
        $time = $trip->departure_time;
        $description = "Your bus ticket to {$destination} on {$date} {$time} has been successfully booked";

        Notification::create([
            'user_id' => $user->id,
            'title' => 'Booking Successful',
            'description' => $description,
            'additional_data' => json_encode([
                'booking_id' => $bookingId,
                'note' => 'Please arrive atleast 30 minutes early to ensure a smooth boarding experience.',
                'help_desk' => 'If you have any questions or need assistance, feel free to contact our support team.',
            ]),
        ]);
    }

    private function recordTransactions(Trip $trip, User $user, array $paymentData, string $bookingId): void
    {
        $amount = number_format($paymentData['amount'] / 100, 2, '.', '');

        $trip->user->driverTripPayments()->create([
            'user_id' => $user->id,
            'trip_id' => $trip->id,
            'title' => TransactionTitle::TRIP_BOOKING->value,
            'amount' => $amount,
            'status' => PaymentStatus::PENDING->value,
        ]);

        $user->transactions()->create([
            'user_id' => $user->id,
            'title' => TransactionTitle::TRIP_BOOKING->value,
            'amount' => $amount,
            'type' => PaymentType::CR,
            'txn_reference' => $paymentData['reference'],
        ]);

        // Disabled for now
        // $this->driverIncrementEarning($trip->user, $amount);

        $charges = $paymentData['metadata']['charges'] ?? [];
        app(ChargeService::class)->transferCharges($charges, $user, 'balance', null);
    }

    private function dispatchTripBookedEvent(User $user): void
    {
        $this->notifier->send(new NotificationDispatchData(
            events: [],
            recipients: $user,
            title: 'Trip Booked',
            body: 'Your bus ticket has been booked successfully!',
            data: [
                'userId' => $user->id,
                'type' => 'trip_booking',
            ]
        ));
    }
}
