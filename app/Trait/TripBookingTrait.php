<?php

namespace App\Trait;

use App\Enum\PaymentMethod;
use App\Enum\PaymentType;
use App\Enum\TripStatus;
use App\Enum\UserType;
use App\Events\TripBooked;
use App\Models\Notification;
use App\Models\Trip;
use App\Models\TripBooking;
use App\Models\User;
use App\Services\Payment\HandlePaymentService;
use App\Services\Payment\PaymentDetailService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

trait TripBookingTrait
{
    use HttpResponse;

    public function processPayment($request, $result, $paymentProcessor = null)
    {
        if (! isset($paymentProcessor)) {
            return $result;
        }

        $trip = $this->handleTripCheck($request);

        if ($trip instanceof \Illuminate\Http\JsonResponse && $trip->getStatusCode() !== 200) {
            return $trip;
        }

        $paymentService = new HandlePaymentService($paymentProcessor);
        $paymentDetails = PaymentDetailService::paystackPayDetails($request, $trip);

        return $paymentService->process($paymentDetails);
    }

    protected function walletPayment($amount_paid, $request, $user)
    {
        if ($response = $this->checkPin($request, $user)) {
            return $response;
        }

        if($amount_paid > $user->wallet) {
            return $this->error(null, "Your balance is insufficient to complete your request",  400);
        }

        if ($user->wallet < $amount_paid) {
            return $this->error(null, "Insufficient balance!", 400);
        }

        if($amount_paid <= 0) {
            return $this->error(null, "Amount cannot be lesser than 0",  400);
        }

        $trip = $this->handleTripCheck($request);

        if ($trip instanceof \Illuminate\Http\JsonResponse && $trip->getStatusCode() !== 200) {
            return $trip;
        }

        try {
            DB::beginTransaction();

            $user = User::with(['transactions'])->findOrFail($user->id);
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
            ->findOrFail($request->trip_id);

            $user->wallet -= $amount_paid;
            $user->save();
            $destination = $trip->destinationRegion?->state?->name . ' > ' . $trip->destinationRegion?->name;

            do {
                $booking_id = getRandomNumber();
            } while(TripBooking::where('booking_id', $booking_id)->exists());

            $ref = getRandomString();

            $paymentLog = $user->paymentLogs()->create([
                'trip_id' => $request->trip_id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'phone_number' => $user->phone_number,
                'amount' => $amount_paid,
                'reference' => $ref,
                'channel' => "wallet",
                'currency' => "NGN",
                'ip_address' => $request->ip(),
                'paid_at' => now(),
                'createdAt' => now(),
                'transaction_date' => now(),
                'status' => "success",
                'type' => PaymentType::TRIP_BOOKING,
            ]);

            TripBooking::create([
                'booking_id' => $booking_id,
                'payment_log_id' => $paymentLog->id,
                'trip_id' => $trip->id,
                'user_id' => $request->user_id ?? $user->id,
                'agent_id' => $user->user_category == [UserType::AGENT] ? $user->id : null,
                'third_party_booking' => $request->third_party_booking ?? 0,
                'selected_seat' => ucfirst($request->selected_seat),
                'trip_type' => $request->trip_type,
                'travelling_with' => $request->travelling_with,
                'third_party_passenger_details' => $request->third_party_passenger_details,
                'amount_paid' => $amount_paid ?? 0,
                'payment_method' => $request->payment_method ?? '',
                'payment_status' => 1,
                'receive_sms' => $request->receive_sms ?? 0,
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
                'trip_id' => $request->trip_id,
                'driver_id' => $trip->user_id,
                'amount' => $amount_paid,
                'status' => 'pending'
            ]);

            $user->transactions()->create([
                'title' => 'Bus ticket purchase',
                'amount' => $amount_paid,
                'type' => "DR",
                'txn_reference' => "wallet"
            ]);

            DB::commit();

            $data = (object) [
                'reference' => $ref,
            ];

            broadcast(new TripBooked($trip, $user));

            return $this->success($data, "Payment successful", 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    protected function handleTripCheck($request)
    {
        if ($request->payment_method === PaymentMethod::PAYSTACK) {
            return $this->tripCheck($request);
        }

        if ($request->payment_method === PaymentMethod::WALLET) {
            return $this->tripCheck($request);
        }

        return null;
    }

    protected function tripCheck($request)
    {
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
        ->where('status', TripStatus::ACTIVE)
        ->find($request->trip_id);

        if(! $trip) {
            return $this->error(null, "Invalid trip ID or trip is no longer available", 400);
        }

        $seats = $trip->vehicle?->seats;

        if (! is_array($seats)) {
            return $this->error(null, "Invalid seats data format", 400);
        }

        $total_seats = count($seats ?? []);
        $bookings = $trip->tripBookings()->where('status', 1)->get();

        $selected_seats = $bookings->pluck('selected_seat')->toArray();

        $already_taken_seats = array_map('ucfirst', array_merge(...array_map(function ($seats) {
            return explode(', ', $seats);
        }, $selected_seats)));

        if($bookings->count() >= $total_seats) {
            throw new \Exception("Number of passengers for this trip already complete", 400);
        }

        $request_seats = array_map('ucfirst', array_map('trim', explode(',', $request->selected_seat)));

        foreach ($request_seats as $seat) {
            if (!in_array($seat, $seats)) {
                return $this->error(null, "Invalid seat selection: $seat", 400);
            }

            if (in_array($seat, $already_taken_seats)) {
                return $this->error(null, "Selected seat already taken: $seat", 400);
            }
        }

        return $trip;
    }

    private function checkPin($request, $user)
    {
        if (!in_array($user->user_category, [UserType::AGENT, UserType::DRIVER])) {
            if (!$request->pin || $request->pin != $user->txn_pin) {
                return $this->error(null, "Invalid transaction pin", 400);
            }
        } else {
            if (!$user->userPin || !Hash::check($request->pin, $user->userPin)) {
                return $this->error(null, "Invalid transaction pin", 400);
            }
        }
    }

}



