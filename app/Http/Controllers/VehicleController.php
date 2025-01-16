<?php

namespace App\Http\Controllers;

use App\Models\TransitCompany;
use App\Models\Vehicle\Vehicle;
use App\Models\vehicle\VehicleBrand;
use App\Models\vehicle\VehicleType;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Tymon\JWTAuth\Facades\JWTAuth;

class VehicleController extends Controller
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
            $request->validate([
                'name' => 'required|string',
                'company_id' => 'required|int',
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
            return response()->json(['error' => collect($e->errors())->flatten()->first()], 400);
        }

        $tCompany = TransitCompany::where('id', $request->company_id);
        if(!$tCompany->exists()) return response()->json(['error' => 'Invalid company ID'], 400);

        $owner = $tCompany->get('user_id')->first();
        if($owner->user_id != $this->user->id) return response()->json(['error' => 'You do not have permission to complete this request'], 400);

        try{

            $vehicle = Vehicle::create([
                'name' => $request->name,
                'company_id' => $request->company_id,
                'brand_id' => $request->brand_id,
                'type_id' => $request->type_id,
                'plate_no' => $request->plate_no,
                'engine_no' => $request->engine_no,
                'chassis_no' => $request->chassis_no,
                'color' => $request->color,
                'seats' => $request->seats,
            ]);

            if($vehicle){
                return response()->json(['message' => 'Vehicle created successfully', 'data' => $vehicle], 200);
            }
        }
        catch(QueryException $e){
            if($e->getCode() === '23000'){
                return response()->json(['error' => 'Cannot add or update a child row: a foreign key constraint fails'], 400);
            }
            else{
                Log::error($e->getMessage());
                return response()->json(['error' => 'An error occured, please contact support'], 400);
            }
        }

    }

    /**
     * Display the specified resource.
     */
    public function show(Vehicle $vehicle)
    {
        return response()->json([
            'data' => $vehicle
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Vehicle $vehicle)
    {
        try{
            $request->validate([
                'name' => 'required|string',
                'company_id' => 'required|int',
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
            return response()->json(['error' => collect($e->errors())->flatten()->first()], 400);
        }

        $tCompany = TransitCompany::where('id', $request->company_id);
        if(!$tCompany->exists()) return response()->json(['error' => 'Invalid company ID'], 400);


        $owner = $tCompany->get(['user_id', 'id'])->first();
        if($owner->user_id != $this->user->id) return response()->json(['error' => 'You do not have permission to complete this request'], 400);

        if($vehicle->company_id != $owner->id) return response()->json(['error' => 'You do not have permission to complete this request'], 400);

        try{

            $status = $vehicle->update([
                'name' => $request->name,
                'company_id' => $request->company_id,
                'brand_id' => $request->brand_id,
                'type_id' => $request->type_id,
                'plate_no' => $request->plate_no,
                'engine_no' => $request->engine_no,
                'chassis_no' => $request->chassis_no,
                'color' => $request->color,
                'seats' => $request->seats,
            ]);

            if($status){
                return response()->json(['message' => 'Vehicle updated successfully', 'data' => $vehicle], 200);
            }
        }
        catch(QueryException $e){
            if($e->getCode() === '23000'){
                return response()->json(['error' => 'Cannot add or update a child row: a foreign key constraint fails'], 400);
            }
            else{
                Log::error($e->getMessage());
                return response()->json(['error' => 'An error occured, please contact support'], 400);
            }
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
        $types = VehicleType::select('name', 'id' )->get();
        return response()->json(['data' => $types], 200);
    }

    public function getVehicleBrands(){
        $brands = VehicleBrand::select('name', 'id')->get();
        return response()->json(['data' => $brands], 200);
    }
}
