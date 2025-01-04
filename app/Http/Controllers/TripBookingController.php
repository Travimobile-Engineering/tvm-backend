<?php

namespace App\Http\Controllers;

use App\Models\Trip;
use App\Models\User;
use App\Models\Transaction;
use App\Models\TripBooking;
use App\Trait\HttpResponse;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\Vehicle\Vehicle;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Log;
use App\Services\TripBookingService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use App\Http\Requests\TripBookingCreateRequest;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\Controllers\Payment\PaystackPaymentController;
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
        return $this->response($this->service->store($request));
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
