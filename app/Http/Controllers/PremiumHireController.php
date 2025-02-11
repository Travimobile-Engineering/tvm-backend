<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddCharterRequest;
use App\Http\Requests\CharterPaymentRequest;
use App\Http\Requests\PremiumHireAddPassengerRequest;
use App\Services\PremiumHireService;
use Illuminate\Http\Request;

class PremiumHireController extends Controller
{
    public function __construct(
        protected PremiumHireService $service
    )
    {}

    public function vehicleLookup(Request $request)
    {
        return $this->service->vehicleLookup($request);
    }

    public function vehicleDetail($id)
    {
        return $this->service->vehicleDetail($id);
    }

    public function addCharter(AddCharterRequest $request)
    {
        return $this->service->addCharter($request);
    }

    public function getCharter($userId)
    {
        return $this->service->getCharter($userId);
    }

    public function payCharter(CharterPaymentRequest $request)
    {
        return $this->service->payCharter($request);
    }

    public function getPaymentRef($reference)
    {
        return $this->service->getPaymentRef($reference);
    }

    public function userBookings($userId)
    {
        return $this->service->userBookings($userId);
    }

    public function addPassenger(PremiumHireAddPassengerRequest $request)
    {
        return $this->service->addPassenger($request);
    }

    public function getPassengers($userId)
    {
        return $this->service->getPassengers($userId);
    }

    public function editPassenger(Request $request)
    {
        return $this->service->editPassenger($request);
    }

    public function deletePassenger($id)
    {
        return $this->service->deletePassenger($id);
    }

    public function cancelBooking(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:premium_hire_bookings,id',
            'reason' => 'required|string',
        ]);

        return $this->service->cancelBooking($request);
    }

    public function review(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'rating' => 'required|numeric|min:1|max:5',
            'comment' => 'nullable|string',
        ]);

        return $this->service->review($request);
    }

    public function getReviews()
    {
        return $this->service->getReviews();
    }

    public function completedBookings($userId)
    {
        return $this->service->completedBookings($userId);
    }

    public function canceledBookings($userId)
    {
        return $this->service->canceledBookings($userId);
    }

    public function upcomingBookings($userId)
    {
        return $this->service->upcomingBookings($userId);
    }

    public function bookingDetails($id)
    {
        return $this->service->bookingDetails($id);
    }

    public function driverCompletedBookings($userId)
    {
        return $this->service->driverCompletedBookings($userId);
    }

    public function driverCanceledBookings($userId)
    {
        return $this->service->driverCanceledBookings($userId);
    }

    public function driverUpcomingBookings($userId)
    {
        return $this->service->driverUpcomingBookings($userId);
    }

    public function driverTripDetails($id)
    {
        return $this->service->driverTripDetails($id);
    }

    public function cancelTrip(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:premium_hire_bookings,id',
            'reason' => 'required|string',
        ]);

        return $this->service->cancelTrip($request);
    }

    public function finishTrip(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:premium_hire_bookings,id',
        ]);

        return $this->service->finishTrip($request);
    }

    public function acceptTrip($id)
    {
        return $this->service->acceptTrip($id);
    }

    public function startTrip($id)
    {
        return $this->service->startTrip($id);
    }
}
