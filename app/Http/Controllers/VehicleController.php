<?php

namespace App\Http\Controllers;

use App\Http\Requests\VehicleCreateRequest;
use App\Models\TransitCompany;
use App\Models\Vehicle\Vehicle;
use App\Models\vehicle\VehicleBrand;
use App\Models\vehicle\VehicleType;
use App\Trait\HttpResponse;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class VehicleController extends Controller
{
    use HttpResponse;

    protected $user;

    public function __construct()
    {
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
    public function store(VehicleCreateRequest $request)
    {
        $tCompany = TransitCompany::with('user')->find($request->company_id);

        if (!$tCompany) {
            return $this->error(null, "Company not found", 404);
        }

        if ($tCompany->user_id != $this->user->id) {
            return $this->error(null, "You do not have permission to complete this request", 400);
        }

        $vehicle = Vehicle::create([
            'name' => $request->name,
            'company_id' => $request->company_id,
            'brand_id' => $request->brand_id,
            'plate_no' => $request->plate_no,
            'engine_no' => $request->engine_no,
            'chassis_no' => $request->chassis_no,
            'color' => $request->color,
            'model' => $request->model,
            'seats' => $request->seats,
        ]);

        return $this->success($vehicle, "Vehicle created successfully", 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Vehicle $vehicle)
    {
        return $this->success($vehicle, "Vehicle retrieved successfully");
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(VehicleCreateRequest $request, Vehicle $vehicle)
    {
        $tCompany = TransitCompany::with('user')->find($request->company_id);

        if (!$tCompany) {
            return $this->error(null, "Company not found", 404);
        }

        if ($tCompany->user_id != $this->user->id) {
            return $this->error(null, "You do not have permission to complete this request", 400);
        }

        if($vehicle->company_id != $tCompany->id) {
            return $this->error(null, "You do not have permission to complete this request", 400);
        }

        $vehicle->update([
            'name' => $request->name,
            'company_id' => $request->company_id,
            'brand_id' => $request->brand_id,
            'plate_no' => $request->plate_no,
            'engine_no' => $request->engine_no,
            'chassis_no' => $request->chassis_no,
            'color' => $request->color,
            'model' => $request->model,
            'seats' => $request->seats,
        ]);

        return $this->success($vehicle, "Vehicle updated successfully");
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function getVehicleTypes()
    {
        $types = VehicleType::select('name', 'id' )->get();

        return $this->success($types, "Vehicle types retrieved successfully");
    }

    public function getVehicleBrands()
    {
        $brands = VehicleBrand::select('name', 'id')->get();

        return $this->success($brands, "Vehicle brands retrieved successfully");
    }
}
