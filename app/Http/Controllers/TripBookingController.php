<?php

namespace App\Http\Controllers;

use App\Models\Trip;
use App\Models\TripBooking;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

class TripBookingController extends Controller
{
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
            $request->validate([
                'trip_id' => 'required|string',
                'user_id' => 'required|int',
                'selected_seat' => 'nullable|string',
                'trip_type' => 'required|int',
                'travelling_with' => 'nullable|string',
                'paid' => 'nullable|int',
                'payment_method' => 'nullable'
            ]);
        }
        catch(ValidationException $e){
            return response()->json(['error' => collect($e->errors())->flatten()->first()], 400);
        }

        $trip = Trip::where('trip_id', $request->trip_id)
        ->where('status', 1)->exists();

        if(!$trip) return response()->json(['error' => 'Invalid trip ID'], 400);

        do $ticket_id = Str::random(14);
        while(TripBooking::where('ticket_id', $ticket_id)->exists());

        $booking = TripBooking::create([
            'ticket_id' => $ticket_id,
            'trip_id' => $request->trip_id,
            'user_id' => $request->user_id,
            'selected_seat' => $request->selected_seat,
            'trip_type' => $request->trip_type,
            'travelling_with' => $request->travelling_with ?? '',
            'paid' => $request->paid ?? 0,
            'payment_method' => $request->payment_method ?? ''
        ]);

        if($booking){
            return response()->json(['message' => 'Booking created successfully', 'data' => $booking], 200);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(TripBooking $tripBooking)
    {
        return response()->json(['booking' => $tripBooking], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, TripBooking $tripBooking)
    {
        try{
            $request->validate([
                'trip_id' => 'required|string',
                'user_id' => 'required|int',
                'selected_seat' => 'nullable|string',
                'trip_type' => 'required|int',
                'travelling_with' => 'nullable|string',
                'paid' => 'nullable|int',
                'payment_method' => 'nullable'
            ]);
        }
        catch(ValidationException $e){
            return response()->json(['error' => collect($e->errors())->flatten()->first()], 400);
        }

        $trip = Trip::where('trip_id', $request->trip_id)
        ->where('status', 1)->exists();

        if(!$trip) return response()->json(['error' => 'Invalid booking ID'], 400);

        $booking = $tripBooking->update([
            'trip_id' => $request->trip_id,
            'user_id' => $request->user_id,
            'selected_seat' => $request->selected_seat,
            'trip_type' => $request->trip_type,
            'travelling_with' => $request->travelling_with ?? '',
            'paid' => $request->paid ?? 0,
            'payment_method' => $request->payment_method ?? ''
        ]);

        if($booking){
            return response()->json(['message' => 'Booking updated successfully', 'data' => $booking], 200);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TripBooking $tripBooking)
    {
        //
    }
}
