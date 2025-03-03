<?php

namespace App\Services;

use App\Enum\MailingEnum;
use App\Enum\PaymentMethod;
use App\Enum\TripStatus;
use App\Enum\UserType;
use App\Http\Resources\AgentProfileResource;
use App\Http\Resources\TripBookingResource;
use App\Http\Resources\TripResource;
use App\Models\Trip;
use App\Models\TripBooking;
use App\Models\User;
use App\Trait\HttpResponse;
use App\Trait\TripBookingTrait;

class AgentService
{
    use HttpResponse, TripBookingTrait;

    public function profile($userId)
    {
        $user = User::with([
                'transitCompany',
            ])
            ->where('id', $userId)
            ->first();

        if (!$user) {
            return $this->error(null, "User not found", 404);
        }

        $data = new AgentProfileResource($user);

        return $this->success($data, "Agent profile");
    }

    public function agentInfo($request)
    {
        $user = User::findOrFail($request->user_id);

        $photo = uploadFile($request, "profile_photo", "agent/profile");

        $user->update([
            'profile_photo' => $photo['url'],
            'public_id' => $photo['public_id'],
            'gender' => $request->gender,
            'nin' => $request->nin,
            'address' => $request->address,
            'next_of_kin_full_name' => $request->next_of_kin_full_name,
            'next_of_kin_relationship' => $request->next_of_kin_relationship,
            'next_of_kin_phone_number' => $request->next_of_kin_phone_number,
        ]);

        return $this->success(null, "Agent information updated successfully");
    }

    public function busSearch($request)
    {
        $trips = Trip::where('departure', $request->departure)
            ->where('destination', $request->destination)
            ->where('departure_date', $request->departure_date)
            ->where('departure_time', $request->departure_time)
            ->where('status', TripStatus::ACTIVE)
            ->get();

        $data = TripResource::collection($trips);

        return $this->success($data, "Bus search result");
    }

    public function buyTicket($request)
    {
        $user = authUser();
        $amount_paid = $request->amount_paid;
        $result = null;
        $paymentProcessor = null;

        match($request->payment_method) {
            PaymentMethod::WALLET => $result = $this->walletPayment($amount_paid, $request, $user),
            default => throw new \Exception('Invalid payment method'),
        };

        return $this->processPayment($request, $result, $paymentProcessor);
    }

    public function ticketSearch($request)
    {
        $user = authUser();
        $query = $request->input('search');

        $tickets = TripBooking::with('user')
            ->where('agent_id', $user->id)
            ->where(function ($q) use ($query) {
                $q->where('booking_id', 'LIKE', "%{$query}%");
            })
            ->orWhereHas('user', function ($q) use ($query) {
                $q->where('first_name', 'LIKE', "%{$query}%")
                ->orWhere('last_name', 'LIKE', "%{$query}%");
            })
            ->get();

        $data = TripBookingResource::collection($tickets);

        return $this->success($data, "Ticket search result");
    }

    public function searchPassenger($request)
    {
        $search = $request->input('search');

        $users = User::select('id', 'first_name', 'last_name', 'phone_number', 'profile_photo')
                ->where('phone_number', $search)
                ->orWhere('first_name', 'LIKE', "%{$search}%")
                ->orWhere('last_name', 'LIKE', "%{$search}%")
                ->get();

        return $this->success($users, "Passenger search result");
    }

    public function addUser($request)
    {
        $fullName = trim($request->input('name'));
        $nameParts = explode(' ', $fullName, 2);

        $firstName = $nameParts[0] ?? null;
        $lastName = $nameParts[1] ?? null;

        $user = User::create([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'phone_number' => $request->phone_number,
            'gender' => $request->gender,
            'nin' => $request->nin,
            'verification_code' => 0000,
            'profile_photo' => null,
            'password' => bcrypt('12345678'),
        ]);

        return $this->success($user, "User created successfully");
    }

    public function bookingHistory($userId)
    {
        $status = request()->query('status');

        $bookingsQuery = TripBooking::with([
                'user' => function ($query) {
                    $query->select('id', 'first_name', 'last_name', 'phone_number');
                },
                'trip' => function ($query) {
                    $query->select('id', 'departure', 'destination', 'departure_date', 'departure_time', 'trip_duration', 'status');
                },
            ])
            ->where('agent_id', $userId);

        if (!empty($status)) {
            $bookingsQuery->whereHas('trip', function ($query) use ($status) {
                $query->where('status', $status);
            });
        }

        $bookings = $bookingsQuery->get();

        return $this->success($bookings, 'Booking History Fetched Successfully');
    }

    public function bookingDetail($bookingId)
    {
        $booking = TripBooking::with([
                'user' => function ($query) {
                    $query->select('id', 'first_name', 'last_name', 'phone_number');
                },
                'trip' => function ($query) {
                    $query->select(
                        'id',
                        'departure',
                        'destination',
                        'departure_date',
                        'departure_time',
                        'trip_duration',
                        'reason',
                        'date_cancelled',
                        'status',
                    );
                },
            ])
            ->where('booking_id', $bookingId)
            ->first();

        if (! $booking) {
            return $this->error('Booking not found', 404);
        }

        return $this->success($booking, 'Booking History Fetched Successfully');
    }

    public function cancelTrip($request, $tripId)
    {
        $trip = Trip::find($tripId);

        if (! $trip) {
            return $this->error('Trip not found', 404);
        }

        $trip->update([
            'reason' => $request->reason,
            'status' => TripStatus::CANCELLED,
        ]);

        return $this->success($trip, 'Trip cancelled successfully');
    }

    public function updateProfile($request)
    {
        $user = User::findOrFail($request->user_id);

        $user->update([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'phone_number' => $request->phone_number,
            'gender' => $request->gender,
            'nin' => $request->nin,
            'next_of_kin_full_name' => $request->next_of_kin_full_name,
            'next_of_kin_relationship' => $request->next_of_kin_relationship,
            'next_of_kin_phone_number' => $request->next_of_kin_phone_number,
        ]);

        return $this->success($user, "Profile updated successfully");
    }

    public function deleteProfile($user)
    {
        $user->delete();
        return $this->success(null, "Account deleted successfully");
    }

    public function sendOtp($request)
    {
        $user = User::where('email', $request->email)->first();

        if (! $user) {
            return $this->error(null, 'Email not found', 404);
        }

        if ($user->verification_code !== 0 || ($user->verification_code_expires_at !== null && $user->verification_code_expires_at >= now())) {
            return $this->error(null, "Code has been sent to you", 400);
        }

        $code = generateUniqueNumber('users', 'verification_code', 5);

        $user->update([
            'verification_code' => $code,
            'verification_code_expires_at' => now()->addMinutes(10),
        ]);

        if ($user) {
            $data = [
                'name' => $user->first_name,
                'code' => $code
            ];
            mailSend(
                MailingEnum::VERIFY_OTP,
                $user,
                "Verify Pin",
                "App\Mail\VerifyPinMail",
                $data
            );
        }

        return $this->success(null, 'Verification code sent successfully');
    }

    public function verifyPin($request)
    {
        $user = User::where('id', $request->user_id)
            ->where('verification_code', $request->code)
            ->whereFuture('verification_code_expires_at')
            ->first();

        if (! $user) {
            return $this->error(null, "Invalid code", 400);
        }

        $user->update([
            'verification_code' => 0,
            'verification_code_expires_at' => null
        ]);

        return $this->success(null, 'Verified successfully');
    }

    public function changePin($request)
    {
        $user = User::with('userPin')->findOrFail($request->user_id);

        $user->userPin()->update([
            'pin' => bcrypt($request->pin)
        ]);

        return $this->success(null, 'Changed successfully');
    }
}





