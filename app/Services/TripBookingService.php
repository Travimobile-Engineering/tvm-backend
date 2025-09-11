<?php

namespace App\Services;

use App\DTO\NotificationDispatchData;
use App\Models\Trip;
use App\Models\User;
use App\Enum\TripStatus;
use App\Enum\PaymentMethod;
use App\Models\Transaction;
use App\Models\TripBooking;
use App\Models\TripPayment;
use App\Trait\HttpResponse;
use Illuminate\Support\Str;
use App\Models\Notification;
use App\Models\TransitCompany;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\QueryException;
use App\Http\Controllers\Payment\PaystackPaymentController;
use App\Http\Resources\TripBookingResource;
use App\Services\Notification\NotificationDispatcher;

class TripBookingService
{
    use HttpResponse;

    protected $user;

    public function __construct(
        protected NotificationDispatcher $notifier
    ){
        $this->user = JWTAuth::user();
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
    public function store($request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show($tripBooking)
    {
        $booking = TripBooking::with([
                'trip.user',
                'user.transitCompany',
                'trip.departureRegion.state',
                'trip.destinationRegion.state',
                'trip.departureRegion.parksWithTransitCompany',
                'trip.destinationRegion.parksWithTransitCompany',
                'trip.vehicle',
            ])
            ->where('booking_id', $tripBooking->booking_id)
            ->first();

        if(!$booking) {
            return $this->error(null, 'Invalid booking ID', 400);
        }

        $data = new TripBookingResource($booking);

        return $this->success($data, 'Booking fetched successfully');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update($request, $tripBooking)
    {
        if($this->user->id != $tripBooking->user_id) {
            return $this->error(null, 'You do not have the permission to complete this request', 400);
        }

        $trip = Trip::where('uuid', $request->trip_id)
            ->where('status', 1)
            ->exists();

        if(!$trip) {
            return $this->error(null, 'Invalid booking ID', 400);
        }

        $tripBooking->update([
            'trip_id' => $request->trip_id,
            'selected_seat' => ucfirst($request->selected_seat),
            'trip_type' => $request->trip_type,
            'travelling_with' => $request->travelling_with ?? '',
            'amount_paid' => $request->amount_paid ?? 0,
            'payment_method' => $request->payment_method ?? '',
            'payment_status' => $request->payment_status
        ]);

        return $this->success($tripBooking, 'Booking updated successfully');
    }

    public function cancelTripBooking($request)
    {
        $booking = TripBooking::with(['user', 'trip.user'])
            ->where('booking_id', $request->booking_id)
            ->firstOrFail();

        if(!in_array($this->user->id, [$booking->user_id, $booking->agent_id])) {
            return $this->error(null, 'You do not have the permission to complete this request', 400);
        }

        $booking->update([
            'reason' => $request->reason,
            'date_canceled' => now(),
            'status' => 0
        ]);

        $this->notifier->send(new NotificationDispatchData(
            events: [],
            recipients: collect([$booking->user, $booking->trip->user])->filter()->unique('id'),
            title: 'Booking Cancelled',
            body: "Booking ID {$booking->booking_id} has been cancelled.",
            data: [
                'booking_id' => $booking->booking_id,
                'type' => 'booking_cancelled',
            ]
        ));

        return $this->success($booking, 'Booking cancelled successfully');
    }

    // New version, optimized and shorter
    public function userBookingHistory($request)
    {
        $user = User::findOrFail($request->user);
        $history = TripBooking::with([
                'trip' => function ($query) {
                    $query->select('id', 'departure', 'destination', 'departure_date', 'trip_duration');
                },
            ])
            ->where('user_id', $user->id)
            ->get();

        return $this->success($history, 'Booking History Fetched Successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TripBooking $tripBooking)
    {
        $tripBooking->delete();
        return $this->success(null, 'Booking deleted successfully');
    }
}
