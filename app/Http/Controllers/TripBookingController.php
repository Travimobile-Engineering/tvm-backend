<?php

namespace App\Http\Controllers;

use App\Models\TripBooking;
use App\Trait\HttpResponse;
use Illuminate\Http\Request;
use App\Services\TripBookingService;
use App\Http\Requests\TripBookingCreateRequest;
use App\Http\Requests\TripBookingUpdateRequest;

class TripBookingController extends Controller
{
    use HttpResponse;
    protected $service;

    public function __construct(TripBookingService $service){
        $this->service = $service;
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(TripBookingCreateRequest $request)
    {
        return $this->service->store($request);
    }

    /**
     * Display the specified resource.
     */
    public function show(TripBooking $tripBooking)
    {
        return $this->response($this->service->show($tripBooking));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(TripBookingUpdateRequest $request, TripBooking $tripBooking)
    {
        return $this->response($this->service->update($request, $tripBooking));

    }

    public function cancelTripBooking(Request $request){
        return $this->response($this->service->cancelTripBooking($request));
    }

    public function getUserTripBookingHistory(Request $request){
        return $this->response($this->service->getUserTripBookingHistory($request));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TripBooking $tripBooking)
    {
        //
    }
}
