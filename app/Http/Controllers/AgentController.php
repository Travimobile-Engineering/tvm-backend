<?php

namespace App\Http\Controllers;

use App\Http\Requests\AgentBookingRequest;
use App\Http\Requests\AgentInfoRequest;
use App\Models\User;
use App\Services\AgentService;
use Illuminate\Http\Request;

class AgentController extends Controller
{
    public function __construct(protected AgentService $service)
    {}

    public function profile()
    {
        return $this->service->profile();
    }

    public function agentInfo(AgentInfoRequest $request)
    {
        return $this->service->agentInfo($request);
    }

    public function busSearch(Request $request)
    {
        return $this->service->busSearch($request);
    }

    public function buyTicket(AgentBookingRequest $request)
    {
        return $this->service->buyTicket($request);
    }

    public function ticketSearch(Request $request)
    {
        return $this->service->ticketSearch($request);
    }

    public function searchPassenger(Request $request)
    {
        return $this->service->searchPassenger($request);
    }

    public function addUser(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'phone_number' => 'required|string|unique:users,phone_number',
            'gender' => 'required|string',
            'nin' => 'nullable|string',
        ]);

        return $this->service->addUser($request);
    }

    public function bookingHistory($userId)
    {
        return $this->service->bookingHistory($userId);
    }

    public function bookingDetail($bookingId)
    {
        return $this->service->bookingDetail($bookingId);
    }

    public function cancelTrip(Request $request, $tripId)
    {
        $request->validate([
            'reason' => 'required|string',
        ]);

        return $this->service->cancelTrip($request, $tripId);
    }

    public function updateProfile(Request $request)
    {
        return $this->service->updateProfile($request);
    }

    public function deleteProfile(User $user)
    {
        return $this->service->deleteProfile($user);
    }

    public function sendOtp(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'email' => 'required|email',
        ]);

        return $this->service->sendOtp($request);
    }

    public function verifyPin(Request $request)
    {
        $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'code' => ['required', 'string'],
        ]);

        return $this->service->verifyPin($request);
    }

    public function changePin(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'pin' => 'required|numeric|digits:4|confirmed'
        ]);

        return $this->service->changePin($request);
    }
}
