<?php

namespace App\Trait;

use App\DTO\NotificationDispatchData;
use App\Enum\PaymentMethod;
use App\Enum\PaymentType;
use App\Enum\TripStatus;
use App\Enum\UserType;
use App\Events\TripBooked;
use App\Models\Notification;
use App\Models\Trip;
use App\Models\TripBooking;
use App\Models\User;
use App\Services\Notification\NotificationDispatcher;
use App\Services\Payment\HandlePaymentService;
use App\Services\Payment\PaymentDetailService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

trait TripBookingTrait
{
    use HttpResponse, PaymentLogTrait;

    public function __construct(
        protected NotificationDispatcher $notifier
    )
    {}

    public function processPayment($request, $result, $paymentProcessor = null, $user = null)
    {
        if (! isset($paymentProcessor)) {
            return $result;
        }

        try {
            DB::beginTransaction();

            $tripCheck = $this->tripCheck($request, $user, lock: true);

            if ($tripCheck instanceof JsonResponse && $tripCheck->getStatusCode() !== 200) {
                DB::rollBack();
                return $tripCheck;
            }

            $trip = $tripCheck;

            $paymentService = new HandlePaymentService($paymentProcessor);
            $paymentDetails = PaymentDetailService::paystackPayDetails($request, $trip);

            $response = $paymentService->process($paymentDetails);

            DB::commit();
            return $response;
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
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

        $trip = $this->handleTripCheck($request, $user);

        if ($trip instanceof JsonResponse && $trip->getStatusCode() !== 200) {
            return $trip;
        }

        try {
            DB::beginTransaction();

            $user = User::with(['transactions'])->findOrFail($user->id);

            $userId = $request->user_id ?? $user->id;
            $passUser = User::findOrFail($userId);
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

            $paymentLog = $this->walletPaymentLog($user, $request, $amount_paid, $ref, PaymentType::TRIP_BOOKING);

            $selectedSeats = explode(',', str_replace(' ', '', $request->selected_seat));
            $travellingWith = collect($request->travelling_with)->filter(function ($passenger) {
                return !empty($passenger['name']) || !empty($passenger['email']) || !empty($passenger['phone_number']) || !empty($passenger['gender']);
            })->values();

            if ($travellingWith->isEmpty()) {
                $travellingWith = null;
            }

            $passengers = collect($travellingWith ?? []);

            $passengers->prepend([
                'name' => "{$passUser->first_name } {$passUser->last_name}",
                'email' => $passUser->email,
                'phone_number' => $passUser->phone_number,
                'gender' => $passUser->gender ?? 'unknown',
            ]);

            $data = [
                'booking_id' => $booking_id,
                'payment_log_id' => $paymentLog->id,
                'trip_id' => $trip->id,
                'user_id' => $request->user_id ?? $user->id,
                'agent_id' => $user->user_category == UserType::AGENT->value ? $user->id : null,
                'third_party_booking' => $request->third_party_booking ?? 0,
                'selected_seat' => $selectedSeats,
                'trip_type' => $request->trip_type,
                'travelling_with' => $request->travelling_with,
                'third_party_passenger_details' => $request->third_party_passenger_details,
                'amount_paid' => $amount_paid ?? 0,
                'payment_method' => $request->payment_method ?? '',
                'payment_status' => 1,
                'receive_sms' => $request->receive_sms ?? 0,
                'passengers' => $passengers,
                'user' => $user,
                'request' => $request,
            ];

            $this->createBooking($data);

            Notification::create([
                'user_id' => $user->id,
                'title' => 'Booking Successful',
                'description' => 'Your bus ticket to '.$destination.' on '.date("M jS Y h:iA",strtotime($trip->departure_at)).' has been successfully booked',
                'additional_data' => [
                    'booking_id' => $booking_id,
                    'note' => 'Please arrive atleast 30 minutes early to ensure a smooth boarding experience.',
                    'help_desk' => 'If you have any questions or need assistance, feel free to contact our support team.',
                ]
            ]);

            $trip->user->driverTripPayments()->create([
                'user_id' => $user->id,
                'trip_id' => $request->trip_id,
                'title' => 'Bus ticket purchase',
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

            return $this->success($data, "Payment successful", 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    protected function handleTripCheck($request, $user)
    {
        if ($request->payment_method === PaymentMethod::PAYSTACK) {
            return $this->tripCheck($request, $user);
        }

        if ($request->payment_method === PaymentMethod::WALLET) {
            return $this->tripCheck($request, $user);
        }

        return null;
    }

    protected function tripCheck($request, $user, $lock = false)
    {
        $query = Trip::with([
            'user.transitCompany',
            'vehicle',
            'tripBookings.user',
            'departureRegion.state',
            'destinationRegion.state',
            'manifest'
        ])
        ->where('id', $request->trip_id)
        ->where('status', TripStatus::UPCOMING);

        if ($lock) {
            $query->lockForUpdate();
        }

        $trip = $query->first();

        if(! $trip) {
            return $this->error(null, "Invalid trip ID or trip is no longer available", 400);
        }

        if ($trip->user_id === $user->id) {
            return $this->error(null, 'Drivers cannot book tickets for their own trip.');
        }

        $seats = $trip->vehicle?->seats;

        if (! is_array($seats)) {
            return $this->error(null, "Invalid seats data format", 400);
        }

        $total_seats = count($seats ?? []);
        $bookings = $trip->tripBookings()->where('status', 1)->get();

        $selected_seats = $bookings->pluck('selected_seat')->toArray();

        $already_taken_seats = array_map('ucfirst', array_merge(...array_map(function ($seats) {
            return $seats;
        }, $selected_seats)));

        if($bookings->count() >= $total_seats) {
            return $this->error(null, "Number of passengers for this trip already complete", 400);
        }

        $selectedSeats = explode(',', str_replace(' ', '', $request->selected_seat));

        $travellingWith = collect($request->travelling_with)->filter(function ($passenger) {
            return !empty($passenger['name']) || !empty($passenger['email']) || !empty($passenger['phone_number']) || !empty($passenger['gender']);
        })->values();

        if ($travellingWith->isEmpty()) {
            $travellingWith = null;
        }

        $userId = $request->user_id ?? $user->id;
        $userPass = User::findOrFail($userId);

        $passengers = collect($travellingWith ?? []);

        $passengers->prepend([
            'name' => "{$userPass->first_name } {$userPass->last_name}",
            'email' => $userPass->email,
            'phone_number' => $userPass->phone_number,
            'gender' => $userPass->gender ?? 'unknown',
        ]);

        if (count($selectedSeats) !== $passengers->count()) {
            return $this->error(null, 'Number of seats must match the number of passengers.', 400);
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
        if (!$user->userPin || !Hash::check($request->pin, $user->userPin->pin)) {
            return $this->error(null, "Invalid transaction pin", 400);
        }
    }

    private function createBooking($data)
    {
        $tripBooking = TripBooking::create([
            'booking_id' => $data['booking_id'],
            'payment_log_id' => $data['payment_log_id'],
            'trip_id' => $data['trip_id'],
            'user_id' => $data['user_id'],
            'agent_id' => $data['agent_id'],
            'third_party_booking' => $data['third_party_booking'],
            'selected_seat' => $data['selected_seat'],
            'trip_type' => $data['trip_type'],
            'travelling_with' => $data['travelling_with'],
            'third_party_passenger_details' => $data['third_party_passenger_details'],
            'amount_paid' => $data['amount_paid'],
            'payment_method' => $data['payment_method'],
            'payment_status' => $data['payment_status'],
            'receive_sms' => $data['receive_sms'],
        ]);

        foreach ($data['passengers'] as $index => $passenger) {
            $tripBooking->tripBookingPassengers()->create([
                'trip_booking_id' => $tripBooking->id,
                'name' => $passenger['name'],
                'email' => $passenger['email'] ?? null,
                'phone_number' => $passenger['phone_number'] ?? "nil",
                'next_of_kin' => $index === 0 ? ($data['user']['next_of_kin'] ?? '') : ($data['request']['third_party_passenger_details'][$index - 1]['name'] ?? ''),
                'next_of_kin_phone_number' => $index === 0 ? ($data['user']['next_of_kin_phone'] ?? '') : ($data['request']['third_party_passenger_details'][$index - 1]['phone_number'] ?? 00000000000),

                'gender' => $passenger['gender'] ?? 'male',
                'selected_seat' => $data['selected_seat'][$index] ?? null,
                'on_seat' => false,
            ]);
        }
    }

}



