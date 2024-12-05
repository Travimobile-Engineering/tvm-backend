<?php

namespace App\Http\Controllers;

use App\Models\Trip;
use App\Models\TripBooking;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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

            try{
                $request->validate([
                    'trip_id' => 'required|string',
                    'user_id' => 'required|int',
                    'selected_seat' => 'nullable|string',
                    'trip_type' => 'required|int',
                    'travelling_with' => 'nullable|string',
                    'amount_paid' => 'nullable|int',
                    'payment_method' => 'nullable',
                    'payment_status' => 'nullable|integer'
                ]);
            }
            catch(ValidationException $e){
                return response()->json(['error' => collect($e->errors())->flatten()->first()], 400);
            }
    
            $trip = Trip::where('trip_id', $request->trip_id)
            ->where('status', 1)->exists();
    
            if(!$trip) return response()->json(['error' => 'Invalid trip ID'], 400);
    
            do $booking_id = Str::random(14);
            while(TripBooking::where('booking_id', $booking_id)->exists());
    
            $booking = TripBooking::create([
                'booking_id' => $booking_id,
                'trip_id' => $request->trip_id,
                'user_id' => $request->user_id,
                'selected_seat' => $request->selected_seat,
                'trip_type' => $request->trip_type,
                'travelling_with' => $request->travelling_with ?? '',
                'amount_paid' => $request->amount_paid ?? 0,
                'payment_method' => $request->payment_method ?? '',
                'payment_status' => $request->payment_status ?? 0,
            ]);
    
            if($booking){
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
                    'user_id' => 'required|int',
                    'selected_seat' => 'nullable|string',
                    'trip_type' => 'required|int',
                    'travelling_with' => 'nullable|string',
                    'amount_paid' => 'nullable|int',
                    'payment_method' => 'nullable',
                    'payment_status' => 'required|integer',
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

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TripBooking $tripBooking)
    {
        //
    }
}
