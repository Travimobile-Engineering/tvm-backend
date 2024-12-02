<?php

namespace App\Http\Controllers;

use App\Models\Vehicle\Vehicle;
use App\Models\vehicle\VehicleBrand;
use App\Models\vehicle\VehicleType;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class VehicleController extends Controller
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
                'name' => 'required|string',
                'brand_id' => 'required|integer',
                'type_id' => 'required|integer',
                'plate_no' => 'required|string',
                'engine_no' => 'required|string',
                'chassis_no' => 'required|string',
                'color' => 'required|string',
                'seats' => 'required|string'
            ]);
        }
        catch(ValidationException $e){
            return response()->json(['error' => collect($e->errors())->flatten()->first()]);
        }

        $vehicle = Vehicle::create([
            'name' => $request->name,
            'vehicle_brand_id' => $request->brand_id,
            'vehicle_type_id' => $request->type_id,
            'plate_no' => $request->plate_no,
            'engine_no' => $request->engine_no,
            'chassis_no' => $request->chassis_no,
            'color' => $request->color,
            'seats' => $request->seats,
        ]);

        if($vehicle){
            return response()->json(['message' => 'Vehicle created successfully']);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Vehicle $vehicle)
    {
        return response()->json([
            'vehicle' => $vehicle
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Vehicle $vehicle)
    {
        try{
            $request->validate([
                'name' => 'required|string',
                'brand_id' => 'required|integer',
                'type_id' => 'required|integer',
                'plate_no' => 'required|string',
                'engine_no' => 'required|string',
                'chassis_no' => 'required|string',
                'color' => 'required|string',
                'seats' => 'required|string'
            ]);
        }
        catch(ValidationException $e){
            return response()->json(['error' => collect($e->errors())->flatten()->first()]);
        }

        $record = $vehicle->update([
            'name' => $request->name,
            'vehicle_brand_id' => $request->brand_id,
            'vehicle_type_id' => $request->type_id,
            'plate_no' => $request->plate_no,
            'engine_no' => $request->engine_no,
            'chassis_no' => $request->chassis_no,
            'color' => $request->color,
            'seats' => $request->seats,
        ]);

        if($record){
            return response()->json(['message' => 'Vehicle updated successfully']);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function getVehicleTypes(){
        $types = VehicleType::pluck('name', 'id');
        return response()->json(['types' => $types]);
    }

    public function getVehicleBrands(){
        $brands = VehicleBrand::pluck('brand_name', 'id');
        return response()->json(['brands' => $brands]);
    }
}
