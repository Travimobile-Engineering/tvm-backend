<?php

namespace App\Http\Controllers;

use App\Http\Requests\AgentAddUserRequest;
use App\Http\Requests\AgentBookingRequest;
use App\Http\Requests\AgentInfoRequest;
use App\Http\Requests\ChangePinRequest;
use App\Http\Requests\ImpersonateDriverRequest;
use App\Http\Requests\NotificationRequest;
use App\Http\Requests\SendPinOtpRequest;
use App\Http\Requests\StartTripRequest;
use App\Http\Requests\TransportOneTimeRequest;
use App\Http\Requests\TransportRecurringRequest;
use App\Http\Requests\ValidatePinRequest;
use App\Http\Requests\VerifyPinRequest;
use App\Services\AgentService;
use App\Services\DriverService;
use App\Services\Trip\TripService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

class AgentController extends Controller
{
    public function __construct(
        protected AgentService $service,
        protected DriverService $driverService,
        protected TripService $tripService,
    ) {}

    public function profile()
    {
        return $this->service->profile();
    }

    public function getAgent($agentId)
    {
        return $this->service->getAgent($agentId);
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', Password::defaults()],
            'confirm_password' => ['required', 'same:new_password'],
        ]);

        return $this->service->changePassword($request);
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

    public function addUser(AgentAddUserRequest $request)
    {
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

    public function deleteProfile(Request $request)
    {
        return $this->service->deleteProfile($request);
    }

    public function sendOtp(SendPinOtpRequest $request)
    {
        return $this->service->sendOtp($request);
    }

    public function verifyPin(VerifyPinRequest $request)
    {
        return $this->service->verifyPin($request);
    }

    public function changePin(ChangePinRequest $request)
    {
        return $this->service->changePin($request);
    }

    public function searchDriver(Request $request)
    {
        $request->validate([
            'search' => 'required|string',
        ]);

        return $this->service->searchDriver($request);
    }

    public function impersonateDriver(ImpersonateDriverRequest $request)
    {
        return $this->service->impersonateDriver($request);
    }

    public function createOneTimeTrip(TransportOneTimeRequest $request)
    {
        return $this->service->createOneTimeTrip($request);
    }

    public function createRecurringTrip(TransportRecurringRequest $request)
    {
        return $this->service->createRecurringTrip($request);
    }

    public function getTrips($userId)
    {
        return $this->service->getTrips($userId);
    }

    public function tripDetails($tripId)
    {
        return $this->service->tripDetails($tripId);
    }

    public function startTrip(StartTripRequest $request)
    {
        return $this->service->startTrip($request);
    }

    public function completeTrip($id)
    {
        return $this->tripService->completeTrip($id);
    }

    public function addBusStop(Request $request)
    {
        $request->validate([
            'state_id' => 'required|integer|exists:states,id',
            'stops' => 'required',
        ]);

        return $this->driverService->addBusStop($request);
    }

    public function getAllBusStops($userId)
    {
        return $this->driverService->getAllBusStops($userId);
    }

    public function getStop($userId, $stateId)
    {
        return $this->driverService->getStop($userId, $stateId);
    }

    public function updateNotification(NotificationRequest $request)
    {
        return $this->service->updateNotification($request);
    }

    public function notifyPassengers(Request $request)
    {
        return $this->service->notifyPassengers($request);
    }

    public function scanTicket(Request $request, $bookingId = null, $seatNo = null)
    {
        return $this->service->scanTicket($request, $bookingId, $seatNo);
    }

    public function validateDriverPin(ValidatePinRequest $request)
    {
        return $this->service->validateDriverPin($request);
    }
}
