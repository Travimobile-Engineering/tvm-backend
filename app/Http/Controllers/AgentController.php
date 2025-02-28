<?php

namespace App\Http\Controllers;

use App\Http\Requests\AgentBookingRequest;
use App\Http\Requests\AgentInfoRequest;
use App\Services\AgentService;
use Illuminate\Http\Request;

class AgentController extends Controller
{
    public function __construct(protected AgentService $service)
    {}

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
}
