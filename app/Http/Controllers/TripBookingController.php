<?php

namespace App\Http\Controllers;

use App\Models\Trip;
use App\Models\TripBooking;
use App\Models\User;
use App\Models\Vehicle\Vehicle;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;

class TripBookingController extends Controller
{
    protected $user;

    public function __construct(){
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
    public function store(Request $request)
    {
        try{

            try{
                $request->validate([
                    'trip_id' => 'required|string',
                    'third_party_booking' => 'nullable|int',
                    'selected_seat' => 'required|string',
                    'trip_type' => 'required|int',
                    'travelling_with' => 'nullable|string',
                    'third_party_passenger_details' => 'nullable|string',
                    'amount_paid' => 'nullable|int',
                    'payment_method' => 'nullable',
                    'payment_status' => 'nullable|integer'
                ]);
            }
            catch(ValidationException $e){
                return response()->json(['error' => collect($e->errors())->flatten()->first()], 400);
            }

            $trip = Trip::where('trip_id', $request->trip_id)
            ->where('status', 1);

            if(!$trip->exists()) return response()->json(['error' => 'Invalid trip ID or trip is no longer available'], 400);

            //get the vehicle for this trip
            $trip = $trip->select('vehicle_id')->first();
            $seats = Vehicle::where('id', $trip->vehicle_id)->pluck('seats')->first();
            $seats = json_decode($seats);
            $trip['seats'] = $seats;

            //total number of seats in this vehicle
            $total_seats = count($trip['seats']);

            //get the total bookings for this trip
            $bookings = TripBooking::where('trip_id', $request->trip_id)->where('status', 1);
            if(count($bookings->get()) >= $total_seats) return response()->json(['error' => 'Number of passengers for this trip already complete'], 400);

            //get the already selected seats in the vehicle for this trip
            $selected_seats = $bookings->pluck('selected_seat')->toArray();

            if(!in_array(ucfirst($request->selected_seat), $seats)) return response()->json(['error' => 'Invalid seat selection'], 400);
            if(in_array(ucfirst($request->selected_seat), $selected_seats)) return response()->json(['error' => 'Selected seat already taken'], 400);

            $available_seats = array_filter($seats, function($seat) use ($selected_seats){
                return !in_array($seat, $selected_seats);
            });

            $trip['available_seats'] = $available_seats;

            do $booking_id = Str::random(14);
            while(TripBooking::where('booking_id', $booking_id)->exists());

            $booking = TripBooking::create([
                'booking_id' => $booking_id,
                'trip_id' => $request->trip_id,
                'user_id' => $this->user->id,
                'third_party_booking' => $request->third_party_booking ?? 0,
                'selected_seat' => ucfirst($request->selected_seat),
                'trip_type' => $request->trip_type,
                'travelling_with' => $request->travelling_with ?? null,
                'third_party_passenger_details' => $request->third_party_passenger_details ?? null,
                'amount_paid' => $request->amount_paid ?? 0,
                'payment_method' => $request->payment_method ?? '',
                'payment_status' => $request->payment_status ?? 0,
            ]);

            if($booking){
                if(count($bookings->get()) >= $total_seats){
                    $trip = Trip::where('trip_id', $request->trip_id)
                    ->update(['status' => 0]);
                }
                return response()->json(['message' => 'Booking created successfully', 'data' => $booking], 200);
            }
        }
        catch(QueryException $e){
            if($e->getCode() === '23000'){
                return response()->json(['error' => 'Integrity constraint violation: Cannot add or update a child row: a foreign key constraint fails'], 400);
            }
            else{
                Log::error($e->getMessage());
                return response()->json(['error' => 'An error occured. Contact support'], 400);
            }
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(TripBooking $tripBooking)
    {
        if($tripBooking->user_id != $this->user->id) return response()->json(['error' => 'You do not have permission to complete this request'], 400);
        return response()->json(['data' => $tripBooking], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, TripBooking $tripBooking)
    {
        try{

            try{
                $request->validate([
                    'trip_id' => 'required|string',
                    'third_party_booking' => 'nullable|int',
                    'selected_seat' => 'required|string',
                    'trip_type' => 'required|int',
                    'travelling_with' => 'nullable|string',
                    'third_party_passenger_details' => 'nullable|string',
                    'amount_paid' => 'nullable|int',
                    'payment_method' => 'nullable',
                    'payment_status' => 'required|integer',
                ]);
            }
            catch(ValidationException $e){
                return response()->json(['error' => collect($e->errors())->flatten()->first()], 400);
            }

            if($this->user->id != $tripBooking->user_id) return response()->json(['error' => 'You do not have the permission to complete this request'], 400);

            $trip = Trip::where('trip_id', $request->trip_id)
            ->where('status', 1)->exists();

            if(!$trip) return response()->json(['error' => 'Invalid booking ID'], 400);

            $booking = $tripBooking->update([
                'trip_id' => $request->trip_id,
                'selected_seat' => ucfirst($request->selected_seat),
                'trip_type' => $request->trip_type,
                'travelling_with' => $request->travelling_with ?? '',
                'amount_paid' => $request->amount_paid ?? 0,
                'payment_method' => $request->payment_method ?? '',
                'payment_status' => $request->payment_status
            ]);

            if($booking){
                return response()->json(['message' => 'Booking updated successfully', 'data' => $tripBooking], 200);
            }
        }
        catch(QueryException $e){
            if($e->getCode() === '23000'){
                return response()->json(['error' => 'Integrity constraint violation: Cannot add or update a child row: a foreign key constraint fails'], 400);
            }
            else{
                Log::error($e->getMessage());
                return response()->json(['error' => 'An error occured. Contact support'], 400);
            }
        }
    }

    public function cancelTripBooking(Request $request){


        $bookingId = $request->booking_id;
        $booking = TripBooking::where('booking_id', $bookingId);
        if(!$booking->exists()) return response()->json(['error' => 'Invalid booking ID'], 400);

        $booking = $booking->first();
        if($this->user->id != $booking->user_id) return response()->json(['error' => 'You do not have the permission to complete this request'], 400);

        $booking->update(['status' => 0]);
        return response()->json(['message' => 'Booking cancelled successfully']);
    }

    public function getUserTripBookingHistory(Request $request){
        $user_id = $request->user;
        $is_email = filter_var($request->user, FILTER_VALIDATE_EMAIL) ? true : false;

        if($is_email){
            $user = User::where('email', $request->user)->select('id')->get()->first();
            $user_id = $user->id;
        }

        if($this->user->id != $user_id) return response()->json(['error' => 'You do not have the permission to complete this request'], 400);

        $history = TripBooking::where('user_id', $user_id)->get();
        return response()->json(['data' => $history]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TripBooking $tripBooking)
    {
        //
    }
}
