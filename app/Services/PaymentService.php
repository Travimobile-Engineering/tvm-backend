<?php

namespace App\Services;

use App\Enum\PaymentType;
use App\Enum\PaystackEvent;
use App\Models\Notification;
use App\Models\Trip;
use App\Models\TripBooking;
use App\Models\User;
use App\Trait\HttpResponse;
use App\Trait\PaymentLogTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    use HttpResponse, PaymentLogTrait;

    public function webhook($request)
    {
        $secretKey = config('paystack.secretKey');
        $signature = $request->header('x-paystack-signature');
        $payload = $request->getContent();

        if (!$signature || $signature !== hash_hmac('sha512', $payload, $secretKey)) {
            return $this->error(null, 'Invalid signature', 400);
        }

        $event = json_decode($payload, true);

        if (isset($event['event']) && $event['event'] === PaystackEvent::CHARGE_SUCCESS) {
            $data = $event['data'];
            $paymentType = $data['metadata']['payment_type'];

            switch ($paymentType) {
                case PaymentType::FUND_WALLET:
                    $this->handleFundWallet($event);
                    break;

                case PaymentType::TRIP_BOOKING:
                    $this->handleTripBooking($event);
                    break;

                default:
                    Log::warning('Unknown payment type', ['payment_type' => $paymentType]);
                    break;
            }
        }

        return response()->json(['status' => true], 200);
    }

    private function handleFundWallet($event)
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
                'amount' => $amount,
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

    private function handleTripBooking($event)
    {
        $paymentData = $event['data'];
        $userId = $paymentData['metadata']['user_id'];
        $user = User::with(['driverTripPayments', 'transactions', 'paymentLogs'])
                ->find($userId);

        if (!$user) {
            throw new \Exception("User with ID: {$userId} not found.");
        }

       $paymentLog = $this->logPayment($user, $event);

        try {
            DB::beginTransaction();

            $paymentData = $event['data'];

            $userId = $paymentData['metadata']['user_id'];

            $tripId = $paymentData['metadata']['trip_id'];
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
                    'manifests'
                ]
            )
            ->findOrFail($tripId);

            $seats = $trip->vehicle?->seats;
            $destination = $trip->destinationRegion?->state?->name . ' > ' . $trip->destinationRegion?->name;

            $bookings = $trip->tripBookings()->where('status', 1)->get();

            $selected_seats = $bookings->pluck('selected_seat')->toArray();
            $available_seats = array_filter($seats, function($seat) use ($selected_seats){
                return !in_array($seat, $selected_seats);
            });

            $trip->available_seats = $available_seats;

            do {
                $booking_id = getRandomNumber();
            } while(TripBooking::where('booking_id', $booking_id)->exists());

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
                'description' => 'Your bus ticket to '.$destination.' on '.date("M jS Y h:iA",strtotime($trip->departure_at)).' has been successfully booked',
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
}


