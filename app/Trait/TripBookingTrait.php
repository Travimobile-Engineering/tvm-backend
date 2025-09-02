<?php

namespace App\Trait;

use App\Models\Trip;
use App\Models\User;
use App\Enum\UserType;
use App\Enum\ChargeType;
use App\Enum\TripStatus;
use App\Enum\PaymentType;
use App\Enum\PaymentMethod;
use App\Enum\PaymentStatus;
use App\Models\TripBooking;
use App\Models\Notification;
use App\Enum\TransactionTitle;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Services\ERP\ChargeService;
use Illuminate\Support\Facades\Hash;
use App\DTO\NotificationDispatchData;
use App\Services\ERP\AgentCommissionService;
use App\Services\Payment\HandlePaymentService;
use App\Services\Payment\PaymentDetailService;
use App\Services\Notification\NotificationDispatcher;
use Illuminate\Support\Facades\Auth;

trait TripBookingTrait
{
    use HttpResponse, PaymentLogTrait, DriverTrait;

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

            $user ??= Auth::user();
            $tripCheck = $this->tripCheck($request, $user, lock: true);

            if ($tripCheck instanceof JsonResponse && $tripCheck->getStatusCode() !== 200) {
                DB::rollBack();
                return $tripCheck;
            }

            $trip = $tripCheck;

            $paymentService = new HandlePaymentService($paymentProcessor);
            $paymentDetails = PaymentDetailService::paystackPayDetails($request, $trip);

            if (isset($paymentDetails['status']) && $paymentDetails['status'] === false) {
                return $this->error(null, $paymentDetails['message'], $paymentDetails['code']);
            }

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
        $validationResponse = $this->validatePayment($request, $amount_paid, $user);
        if ($validationResponse) {
            return $validationResponse;
        }

        $trip = $this->handleTripCheck($request, $user);
        if ($trip instanceof JsonResponse && $trip->getStatusCode() !== 200) {
            return $trip;
        }

        return $this->processPaymentTransaction($amount_paid, $request, $user);
    }

    protected function validatePayment($request, $amount_paid, $user)
    {
        $chargesSum = array_sum((array) $request->charges);

        if ($chargesSum != $this->getCharges($user)) {
            return $this->error(null, "Charges paid does not match the total charges", 400);
        }

        if ($response = $this->checkPin($request, $user)) {
            return $response;
        }

        if ($amount_paid <= 0) {
            return $this->error(null, "Amount must be greater than 0", 400);
        }

        if ($user->wallet_amount < $amount_paid) {
            return $this->error(null, "Your balance is insufficient to complete your request", 400);
        }

        return null;
    }

    protected function processPaymentTransaction($amount_paid, $request, $user)
    {
        try {
            DB::beginTransaction();

            $userId = $request->user_id ?? $user->id;
            $passenger = User::findOrFail($userId);
            $getTrip = Trip::with(
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

            // Process Wallet and Transactions
            $this->updateUserWallet($amount_paid, $user);

            // Create Booking and Log Payment
            $bookingData = $this->createBookingAndLogPayment($request, $passenger, $user, $amount_paid, $getTrip);

            // Send Notifications
            $data = $this->sendBookingNotification($user, $bookingData['booking_id'], $getTrip, $bookingData['ref']);

            if ($user->user_category == UserType::AGENT->value) {
                $passengerCollect = collect($bookingData['travelling_with'] ?? []);
                $passengerCount = 1 + $passengerCollect->count();

                // Distribute Agent Commission
                $this->distributeAgentCommission($passenger, $user, $passengerCount, $bookingData['booking_id']);

                // After the booking is completed, automatically check for level upgrade
                $user->checkAndUpgradeLevel(); // This will upgrade the agent if their bookings exceed the threshold
            }

            $charges = $request->charges ?? [];
            app(ChargeService::class)->transferCharges($charges, $user, "balance", "wallet");

            DB::commit();

            return $this->success($data, "Payment successful");
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->error(null, "An error occurred while processing your request: " . $th->getMessage(), 400);
        }
    }

    protected function updateUserWallet($amount_paid, $user)
    {
        if ($user->wallet >= $amount_paid) {
            $this->userIncrementBalance($user, $user->wallet);
            $user->update(['wallet' => 0]);
        }
    }

    protected function createBookingAndLogPayment($request, $passUser, $user, $amount_paid, $trip)
    {
        $this->userDecrementBalance($user, $amount_paid);

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
            'next_of_kin' => $passUser->next_of_kin_full_name ?? null,
            'next_of_kin_phone_number' => $passUser->next_of_kin_phone_number ?? null,
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
            'travelling_with' => $travellingWith,
            'third_party_passenger_details' => $request->third_party_booking === 1 ? $request->third_party_passenger_details : null,
            'amount_paid' => $amount_paid ?? 0,
            'payment_method' => $request->payment_method ?? '',
            'payment_status' => 1,
            'receive_sms' => $request->receive_sms ?? 0,
            'passengers' => $passengers,
            'user' => $user,
            'request' => $request,
        ];

        $this->createBooking($data);

        $trip->user->driverTripPayments()->create([
            'user_id' => $user->id,
            'trip_id' => $request->trip_id,
            'title' => TransactionTitle::TRIP_BOOKING->value,
            'amount' => $amount_paid,
            'status' => PaymentStatus::PENDING->value,
        ]);

        $user->transactions()->create([
            'title' => TransactionTitle::TRIP_BOOKING->value,
            'amount' => $amount_paid,
            'type' => "DR",
            'txn_reference' => "wallet"
        ]);

        // Disabled for now
        //$this->driverIncrementEarning($trip->user, $amount_paid);

        return [
            'booking_id' => $booking_id,
            'ref' => $ref,
            'travelling_with' => $travellingWith,
        ];
    }

    protected function distributeAgentCommission($passUser, $user, $passengerCount, $bookingId)
    {
        app(AgentCommissionService::class)->distributeAgentCommission($passUser, $user, $passengerCount, $bookingId);
    }

    protected function sendBookingNotification($user, $booking_id, $trip, $ref)
    {
        $destination = "{$trip->destinationRegion?->state?->name} > {$trip->destinationRegion?->name}";
        $date = \Carbon\Carbon::parse($trip->departure_date)->format('Y-m-d');
        $time = $trip->departure_time;
        $description = "Your bus ticket to {$destination} on {$date} {$time} has been successfully booked";

        Notification::create([
            'user_id' => $user->id,
            'title' => 'Booking Successful',
            'description' => $description,
            'additional_data' => [
                'booking_id' => $booking_id,
                'note' => 'Please arrive at least 30 minutes early to ensure a smooth boarding experience.',
                'help_desk' => 'If you have any questions or need assistance, feel free to contact our support team.',
            ]
        ]);

        $data = (object) [
            'reference' => $ref,
        ];

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

        return $data;
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

        if ((int) $request->third_party_booking === 0) {
            $passengers = collect($travellingWith ?? []);
            $passengers->prepend([
                'name' => "{$userPass->first_name } {$userPass->last_name}",
                'email' => $userPass->email,
                'phone_number' => $userPass->phone_number,
                'gender' => $userPass->gender ?? 'unknown',
                'next_of_kin' => $userPass->next_of_kin_full_name ?? null,
                'next_of_kin_phone_number' => $userPass->next_of_kin_phone_number ?? null,
            ]);
        } else {
            $passengers = collect($request->third_party_passenger_details);
        }

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
            'travelling_with' => $data['third_party_booking'] === 0 ? $data['travelling_with'] : null,
            'third_party_passenger_details' => $data['third_party_passenger_details'],
            'amount_paid' => $data['amount_paid'],
            'payment_method' => $data['payment_method'],
            'payment_status' => $data['payment_status'],
            'receive_sms' => $data['receive_sms'],
            'charges' => $data['request']['charges'],
        ]);

        if ((int) $data['third_party_booking'] === 0) {
            foreach ($data['passengers'] as $index => $passenger) {
                $tripBooking->tripBookingPassengers()->create([
                    'trip_booking_id' => $tripBooking->id,
                    'name' => $passenger['name'],
                    'email' => $passenger['email'] ?? null,
                    'phone_number' => $passenger['phone_number'] ?? "nil",
                    'next_of_kin' => $passenger['next_of_kin'] ?? '',
                    'next_of_kin_phone_number' => $passenger['next_of_kin_phone_number'] ?? '',
                    'gender' => $passenger['gender'] ?? 'male',
                    'selected_seat' => $data['selected_seat'][$index] ?? null,
                    'on_seat' => false,
                ]);
            }
        }
    }

    private function getCharges($user)
    {
        $chargeTypes = [
            ChargeType::ADMIN->value,
            ChargeType::VAT->value,
        ];

        if ($user->inbox_notifications) {
            $chargeTypes[] = ChargeType::SMS->value;
        }

        $charges = getCharge($chargeTypes);
        return array_sum($charges);
    }
}



