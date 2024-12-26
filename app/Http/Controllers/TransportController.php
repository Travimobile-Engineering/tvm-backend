<?php

namespace App\Http\Controllers;

use App\Http\Requests\TransportOneTimeRequest;
use App\Http\Requests\TransportRecurringRequest;
use App\Services\Transport\TransportService;
use Illuminate\Http\Request;

class TransportController extends Controller
{
    protected $service;

    public function __construct(TransportService $service)
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

}
