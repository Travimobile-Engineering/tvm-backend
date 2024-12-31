<?php

namespace App\Services;

use App\Enum\DocumentStatus;
use App\Http\Resources\BusStopResource;
use App\Models\User;
use App\Trait\HttpResponse;
use Illuminate\Support\Facades\DB;

class DriverService
{
    use HttpResponse;

    public function addDriverInfo($request)
    {
        $user = User::with(['driverVehicle', 'documents'])
            ->findOrFail($request->user_id);

        DB::beginTransaction();

        try {
            $profilePhoto = uploadFile($request, 'profile_photo', 'driver/documents');

            $user->update([
                'transit_company_union_id' => $request->transit_company_union_id,
                'profile_photo' => $profilePhoto['url'],
                'public_id' => $profilePhoto['public_id'],
            ]);

            $user->driverVehicle()->create([
                'vehicle_year' => $request->vehicle_year,
                'vehicle_model' => $request->vehicle_model,
                'vehicle_color' => $request->vehicle_color,
                'plate_number' => $request->plate_number,
                'vehicle_type' => $request->vehicle_type,
                'vehicle_capacity' => $request->vehicle_capacity,
                'seats' => $request->seats,
                'seat_row' => $request->seat_row,
                'seat_column' => $request->seat_column,
            ]);

            if ($request->hasFile('license_photo')) {
                $licensePhoto = uploadFile($request, 'license_photo', 'driver/documents');
                $user->documents()->create([
                    'type' => 'license',
                    'image_url' => $licensePhoto['url'],
                    'public_id' => $licensePhoto['public_id'],
                    'number' => $request->license_number,
                    'expiration_date' => $request->license_expiration_date,
                    'status' => DocumentStatus::PENDING,
                ]);
            }

            if ($request->hasFile('nin_photo')) {
                $ninPhoto = uploadFile($request, 'nin_photo', 'driver/documents');
                $user->documents()->create([
                    'type' => 'nin',
                    'image_url' => $ninPhoto['url'],
                    'public_id' => $ninPhoto['public_id'],
                    'number' => $request->nin,
                    'status' => DocumentStatus::PENDING,
                ]);
            }

            if ($request->hasFile('vehicle_insurance_photo')) {
                $vehiclePhoto = uploadFile($request, 'vehicle_insurance_photo', 'driver/documents');
                $user->documents()->create([
                    'type' => 'vehicle_insurance',
                    'image_url' => $vehiclePhoto['url'],
                    'public_id' => $vehiclePhoto['public_id'],
                    'expiration_date' => $request->vehicle_insurance_expiration_date,
                    'status' => DocumentStatus::PENDING,
                ]);
            }

            DB::commit();

            return $this->success($user, "Driver information added successfully", 201);
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    public function addBusStop($request)
    {
        $user = User::with(['busStops'])
            ->findOrFail($request->user_id);

        $user->busStops()->updateOrCreate(
            [
                'state_id' => $request->state_id,
            ],
            [
                'stops' => $request->stops
            ]
        );

        return $this->success(null, "Added successfully", 201);
    }

    public function getAllBusStops($userId)
    {
        $user = User::with(['busStops.state'])
            ->findOrFail($userId);

        $data = BusStopResource::collection($user->busStops);

        return $this->success($data, "Bus Stops");
    }

    public function getStop($userId, $stateId)
    {
        $user = User::with(['busStops.state'])->findOrFail($userId);

        $stop = $user->busStops()->where('state_id', $stateId)->firstOrFail();

        $data = [
            'state' => $stop->state->name,
            'stops' => $stop->stops,
        ];

        return $this->success($data, 'Stops retrieved successfully');
    }

}

