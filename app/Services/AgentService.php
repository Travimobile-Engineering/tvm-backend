<?php

namespace App\Services;

use App\Enum\PaymentMethod;
use App\Enum\TripStatus;
use App\Http\Resources\TripBookingResource;
use App\Http\Resources\TripResource;
use App\Models\Trip;
use App\Models\TripBooking;
use App\Models\User;
use App\Services\Payment\PaystackPaymentProcessor;
use App\Trait\HttpResponse;
use App\Trait\TripBookingTrait;
use Illuminate\Support\Facades\Auth;

class AgentService
{
    use HttpResponse, TripBookingTrait;

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
        $trips = Trip::where('type', $request->type)
            ->where('departure', $request->departure)
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
        $query = $request->input('query');

        $tickets = TripBooking::where('user_id', $user->id)
            ->where(function($q) use ($query) {
                $q->where('booking_id', 'LIKE', "%{$query}%")
                ->orWhere('travelling_with', 'LIKE', "%{$query}%")
                ->orWhereJsonContains('travelling_with', ['name' => $query]);
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
}





