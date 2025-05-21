<?php

namespace App\Trait;

use App\DTO\NotificationDispatchData;
use App\Enum\PaymentMethod;
use App\Enum\PaymentType;
use App\Enum\TripStatus;
use App\Events\TripBooked;
use App\Events\WalletFunded;
use App\Models\Notification;
use App\Models\PaymentLog;
use App\Models\PremiumHireBooking;
use App\Models\Trip;
use App\Models\TripBooking;
use App\Models\User;
use App\Models\Vehicle\Vehicle;
use App\Services\Notification\NotificationDispatcher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

trait PaymentTrait
{
    use HttpResponse, PaymentLogTrait;

    public function __construct(
        protected NotificationDispatcher $notifier
    )
    {}

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

            $this->notifier->send(new NotificationDispatchData(
                events: [
                    [
                        'class' => WalletFunded::class,
                        'payload' => [
                            'type' => 'wallet_funded',
                            'message' => "₦{$formattedAmount} has been added to your wallet.",
                            'userId' => $user->id,
                            'amount' => $formattedAmount,
                        ],
                    ],
                ],
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

            $selectedSeats = explode(',', str_replace(' ', '', $selectedSeat));
            $travellingWith = collect($travellingWith)->filter(function ($passenger) {
                return !empty($passenger['name']) || !empty($passenger['email']) || !empty($passenger['phone_number']) || !empty($passenger['gender']);
            })->values();

            if ($travellingWith->isEmpty()) {
                $travellingWith = null;
            }

            $passengers = collect($travellingWith ?? []);

            $passengers->prepend([
                'name' => $user->first_name . ' ' . $user->last_name,
                'email' => $user->email,
                'phone_number' => $user->phone_number,
                'gender' => $user->gender ?? 'unknown',
            ]);

            $tripBooking = TripBooking::create([
                'booking_id' => $booking_id,
                'payment_log_id' => $paymentLog->id,
                'trip_id' => $tripId,
                'user_id' => $user->id,
                'third_party_booking' => $thirdPartyBooking ?? 0,
                'selected_seat' => $selectedSeats,
                'trip_type' => $tripType,
                'travelling_with' => $travellingWith ?? null,
                'third_party_passenger_details' => $thirdPartyPassenger ?? null,
                'amount_paid' => $formattedAmount ?? 0,
                'payment_method' => $paymentMethod ?? '',
                'payment_status' => $payStatus ?? 0,
                'receive_sms' => 0,
            ]);

            foreach ($passengers as $index => $passenger) {
                $tripBooking->tripBookingPassengers()->create([
                    'trip_booking_id' => $tripBooking->id,
                    'name' => $passenger['name'],
                    'email' => $passenger['email'] ?? null,
                    'phone_number' => $passenger['phone_number'],
                    'next_of_kin' => $index === 0 ? ($user->next_of_kin ?? '') : ($thirdPartyPassenger[$index - 1]['name'] ?? ''),
                    'next_of_kin_phone_number' => $index === 0 ? ($user->next_of_kin_phone ?? '') : ($thirdPartyPassenger[$index - 1]['phone_number'] ?? ''),

                    'gender' => $passenger['gender'] ?? 'unknown',
                    'selected_seat' => $selectedSeats[$index] ?? null,
                    'on_seat' => false,
                ]);
            }

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

            $trip->user->driverTripPayments()->create([
                'user_id' => $user->id,
                'trip_id' => $tripId,
                'title' => PaymentType::TRIP_BOOKING,
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

            $this->notifier->send(new NotificationDispatchData(
                events: [
                    [
                        'class' => TripBooked::class,
                        'payload' => [
                            'type' => 'trip_booking',
                            'message' => 'Your bus ticket has been booked successfully!',
                            'userId' => $user->id,
                        ],
                    ]
                ],
                recipients: $user,
                title: 'Trip Booked',
                body: 'Your bus ticket has been booked successfully!',
                data: [
                    'userId' => $user->id,
                    'type' => 'trip_booking',
                ]
            ));
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
            $vehicle =  Vehicle::with('user')->find($vehicleId);
            $type = PaymentType::PREMIUM_HIRE;
            $user = User::with([
                    'transactions',
                    'paymentLogs',
                    'premiumHireBookings'
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
                'bus_stops' => $busStops,
                'luggage' => $luggage,
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
        ])->exists();

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
}

