<?php

namespace App\Http\Controllers;

use App\Http\Requests\TransportOneTimeRequest;
use App\Http\Requests\TransportRecurringRequest;
use App\Services\Trip\TripService;
use Illuminate\Http\Request;

class TripController extends Controller
{
    protected $service;

    public function __construct(TripService $service)
    {
        $this->service = $service;
    }

    public function createOneTime(TransportOneTimeRequest $request)
    {
        return $this->service->createOneTime($request);
    }

    public function getOneTime($id)
    {
        return $this->service->getOneTime($id);
    }

    public function getUserOneTimes($userId)
    {
        return $this->service->getUserOneTimes($userId);
    }

    public function editOneTime(Request $request, $id)
    {
        return $this->service->editOneTime($request, $id);
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
            'trip_id' => ['required', 'integer', 'exists:trips,id'],
        ]);

        return $this->service->startTrip($request);
    }

}
