<?php

namespace App\Http\Controllers;

use App\Models\Trip;
use App\Trait\HttpResponse;
use Illuminate\Http\Request;
use App\Services\Trip\TripService;
use App\Http\Requests\TransportOneTimeRequest;
use App\Http\Requests\TransportRecurringRequest;
use App\Services\AgentService;

class TripController extends Controller
{
    use HttpResponse;

    public function __construct(
        protected TripService $service,
        protected AgentService $agentService,
    )
    {}

    public function createOneTime(TransportOneTimeRequest $request)
    {
        return $this->service->createOneTime($request);
    }

    public function store(TransportOneTimeRequest $request)
    {
        return $this->createOneTime($request);
    }

    public function getOneTime($id)
    {
        return $this->service->getOneTime($id);
    }

    public function getTrip(Trip $trip){
        return $this->service->getTrip($trip);
    }

    public function getUserOneTimes($userId)
    {
        return $this->service->getUserOneTimes($userId);
    }

    public function editOneTime(Request $request, $id)
    {
        return $this->service->editOneTime($request, $id);
    }

    public function getTrips()
    {
        return $this->service->getTrips();
    }

    public function createRecurring(TransportRecurringRequest $request)
    {
        return $this->service->createRecurring($request);
    }

    public function getRecurring($id)
    {
        return $this->service->getRecurring($id);
    }

    public function getUserRecurrings($userId)
    {
        return $this->service->getUserRecurrings($userId);
    }

    public function editRecurring(Request $request, $id)
    {
        return $this->service->editRecurring($request, $id);
    }

    public function cancelTrip(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string'
        ]);

        return $this->service->cancelTrip($request, $id);
    }

    public function completeTrip($id)
    {
        return $this->service->completeTrip($id);
    }

    public function getUpcomingTrips($userId)
    {
        return $this->service->getUpcomingTrips($userId);
    }

    public function getCompletedTrips($userId)
    {
        return $this->service->getCompletedTrips($userId);
    }

    public function getCancelledTrips($userId)
    {
        return $this->service->getCancelledTrips($userId);
    }

    public function getAllTrips($userId)
    {
        return $this->service->getAllTrips($userId);
    }

    public function getAll()
    {
        return $this->service->getAll();
    }

    public function getManifestInfo($id, $userId)
    {
        return $this->service->getManifestInfo($id, $userId);
    }

    public function startTrip(Request $request)
    {
        $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'trip_id' => ['required', 'integer', 'exists:trips,id'],
        ]);

        return $this->service->startTrip($request);
    }

    public function getBusStops($destinationId)
    {
        return $this->service->getBusStops($destinationId);
    }

    public function getPopularTrips()
    {
        return $this->service->getPopularTrips();
    }

    public function downloadTicket($bookingId)
    {
        return $this->service->downloadTicket($bookingId);
    }

    public function extendTime(Request $request)
    {
        $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'trip_extended_time' => ['required', 'regex:/^\d{2}:\d{2}$/'],
        ]);

        return $this->service->extendTime($request);
    }

    public function notifyPassengers(Request $request)
    {
        return $this->agentService->notifyPassengers($request);
    }

    public function tripExtendTime(Request $request)
    {
        $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'trip_id' => ['required', 'integer', 'exists:trips,id'],
            'trip_extended_time' => ['required', 'regex:/^\d{2}:\d{2}$/'],
        ]);

        return $this->service->tripExtendTime($request);
    }

}
