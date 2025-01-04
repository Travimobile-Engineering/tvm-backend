<?php

namespace App\Http\Controllers;

use App\Models\Trip;
use App\Models\TripBooking;
use App\Trait\HttpResponse;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\TransitCompany;
use Illuminate\Support\Carbon;
use App\Models\Vehicle\Vehicle;
use App\Services\ValidationRules;
use App\Services\Trip\TripService;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\QueryException;
use App\Http\Requests\TransportOneTimeRequest;
use Illuminate\Validation\ValidationException;
use App\Http\Requests\TransportRecurringRequest;

class TripController extends Controller
{
    use HttpResponse;
    protected $service;

    public function __construct(TripService $service)
    {
        $this->service = $service;
    }

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

        return $this->response($this->service->getTrip($trip));
    }

    public function getUserOneTimes($userId)
    {
        return $this->service->getUserOneTimes($userId);
    }

    public function editOneTime(Request $request, $id)
    {
        return $this->service->editOneTime($request, $id);
    }

    public function getTrips(Request $request){
        return $this->service->getTrips($request);
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

    public function getBusStops($destinationId)
    {
        return $this->service->getBusStops($destinationId);
    }

}
