<?php

namespace App\Http\Controllers;

use App\Models\Trip;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

class TripController extends Controller
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
                'vehicle_id' => 'required|int',
                'transit_company_id' => 'required|int',
                'route_id' => 'required|int',
                'price' => 'required|int',
                'departure_at' => 'required|date',
                'estimated_arrival_at' => 'required|date',
                'means' => 'nullable|string',
                
            ]);

        }catch(ValidationException $e){
            return response()->json(['error' => collect($e->errors())->flatten()->first()], 400);
        }

        try{

            do $trip_id = Str::random(14);
            while(Trip::where('trip_id', $trip_id)->exists());
    
            $trip = Trip::create([
                'trip_id' => $trip_id,
                'vehicle_id' => $request->vehicle_id,
                'transit_company_id' => $request->transit_company_id,
                'route_id' => $request->route_id,
                'price' => $request->price,
                'departure_at' => $request->departure_at,
                'estimated_arrival_at' => $request->estimated_arrival_at,
                'means' => $request->means ?? 1
            ]);
    
            if($trip){
                return response()->json(['message' => 'Trip created successfully',  'data' => $trip], 200);
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
    public function show(Trip $trip)
    {
        if($trip) return response()->json(['data' => $trip], 200);
        else return response()->json(['error' => 'not found'], 400);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Trip $trip)
    {
        if($trip){
            try{

                $request->validate([
                    'vehicle_id' => 'required|int',
                    'transit_company_id' => 'required|int',
                    'route_id' => 'required|int',
                    'price' => 'required|int',
                    'departure_at' => 'required|date',
                    'estimated_arrival_at' => 'required|date',
                    'means' => 'nullable|string',
                    
                ]);
    
            }catch(ValidationException $e){
                return response()->json(['error' => collect($e->errors())->flatten()->first()], 400);
            }
    
            try{

                $status = $trip->update([
                    'vehicle_id' => $request->vehicle_id,
                    'transit_company_id' => $request->transit_company_id,
                    'route_id' => $request->route_id,
                    'price' => $request->price,
                    'departure_at' => $request->departure_at,
                    'estimated_arrival_at' => $request->estimated_arrival_at,
                    'means' => $request->means ?? 1
                ]);
        
                if($status){
                    return response()->json(['message' => 'Trip updated successfully', 'data' => $trip], 200);
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
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Trip $trip)
    {
        //
    }

    public function search(Request $request){
        
    }

    public function getAllTrips(Request $request){
        $trips = [];
        if($request->date) $trips = Trip::where('departure_at', '>=', $request->date.' '.$request->time ?? '')->get();
        else $trips = Trip::all();
        return response()->json(['data '=> $trips], 200);
    }
}
