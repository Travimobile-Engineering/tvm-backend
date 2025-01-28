<?php

namespace App\Services;

use App\Enum\DocumentStatus;
use App\Http\Resources\BusStopResource;
use App\Models\Document;
use App\Models\User;
use App\Trait\DriverTrait;
use App\Trait\HttpResponse;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Support\Facades\DB;

class DriverService
{
    use HttpResponse, DriverTrait;

    public function addDriverInfo($request)
    {
        $user = User::with(['vehicle', 'documents', 'transitCompany'])
            ->findOrFail($request->user_id);

        $accountExist = hasOnboarded($request->user_id);

        if($accountExist) {
            return $this->error(null, "Account already exist!", 400);
        }

        $fileKeys = ['profile_photo', 'license_photo', 'nin_photo', 'vehicle_insurance_photo'];

        DB::beginTransaction();

        try {
            $fileUploads = uploadFilesBatch($request, $fileKeys, 'driver/documents');

            $company = $this->createTransitCompany($user, $request);

            $seatsString = $request->seats;
            $seatSplit = explode(',', $seatsString);

            $seats = array_map(function ($seat) {
                return trim($seat, '"');
            }, $seatSplit);

            $user->vehicle()->create([
                'company_id' => $company->id,
                'brand_id' => 0,
                'year' => $request->vehicle_year,
                'model' => $request->vehicle_model,
                'color' => $request->vehicle_color,
                'plate_no' => $request->plate_number,
                'type' => $request->vehicle_type,
                'capacity' => $request->vehicle_capacity,
                'seats' => $seats,
                'seat_row' => $request->seat_row,
                'seat_column' => $request->seat_column,
            ]);

            $documentTypes = [
                'license_photo' => [
                    'type' => 'license',
                    'extra_fields' => [
                        'number' => $request->license_number,
                        'expiration_date' => $request->license_expiration_date,
                    ]
                ],
                'nin_photo' => [
                    'type' => 'nin',
                    'extra_fields' => [
                        'number' => $request->nin,
                    ]
                ],
                'vehicle_insurance_photo' => [
                    'type' => 'vehicle_insurance',
                    'extra_fields' => [
                        'expiration_date' => $request->vehicle_insurance_expiration_date,
                    ]
                ],
            ];

            foreach ($documentTypes as $key => $docDetails) {
                if (!empty($fileUploads[$key]['url']) || ($docDetails['type'] === 'nin' && $request->filled('nin'))) {
                    $user->documents()->create([
                        'type' => $docDetails['type'],
                        'image_url' => $fileUploads[$key]['url'] ?? null,
                        'public_id' => $fileUploads[$key]['public_id'] ?? null,
                        'number' => $docDetails['extra_fields']['number'] ?? null,
                        'expiration_date' => $docDetails['extra_fields']['expiration_date'] ?? null,
                        'status' => DocumentStatus::PENDING,
                    ]);
                }
            }

            $user->update([
                'gender' => $request->gender,
                'next_of_kin_full_name' => $request->next_of_kin_full_name,
                'next_of_kin_phone_number' => $request->next_of_kin_phone_number,
                'next_of_kin_relationship' => $request->next_of_kin_relationship,
                'transit_company_union_id' => $request->transit_company_union_id,
                'profile_photo' => $fileUploads['profile_photo']['url'] ?? null,
                'public_id' => $fileUploads['profile_photo']['public_id'] ?? null,
                'driver_verified' => true,
            ]);

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

    public function removeDocument($id)
    {
        $document = Document::findOrFail($id);

        Cloudinary::destroy($document?->public_id);

        $document->delete();

        return $this->success(null, "Document removed successfully");
    }

    public function updateDriverDocuments($request)
    {
        $user = User::with('documents')->findOrFail($request->user_id);

        DB::beginTransaction();

        try {
            if ($request->hasFile('license_photo')) {
                $oldPublicId = optional($user->documents->where('type', 'license')->first())->public_id;
                $licensePhoto = uploadFile($request, 'license_photo', 'driver/documents', $oldPublicId);

                $user->documents()->updateOrCreate(
                    ['type' => 'license'],
                    [
                        'image_url' => $licensePhoto['url'],
                        'public_id' => $licensePhoto['public_id'],
                        'number' => $request->license_number,
                        'expiration_date' => $request->license_expiration_date,
                        'status' => DocumentStatus::PENDING,
                    ]
                );
            }

            if ($request->hasFile('nin_photo')) {
                $oldPublicId = optional($user->documents->where('type', 'nin')->first())->public_id;
                $ninPhoto = uploadFile($request, 'nin_photo', 'driver/documents', $oldPublicId);

                $user->documents()->updateOrCreate(
                    ['type' => 'nin'],
                    [
                        'image_url' => $ninPhoto['url'],
                        'public_id' => $ninPhoto['public_id'],
                        'number' => $request->nin,
                        'status' => DocumentStatus::PENDING,
                    ]
                );
            }

            if ($request->hasFile('vehicle_insurance_photo')) {
                $oldPublicId = optional($user->documents->where('type', 'vehicle_insurance')->first())->public_id;
                $vehiclePhoto = uploadFile($request, 'vehicle_insurance_photo', 'driver/documents', $oldPublicId);

                $user->documents()->updateOrCreate(
                    ['type' => 'vehicle_insurance'],
                    [
                        'image_url' => $vehiclePhoto['url'],
                        'public_id' => $vehiclePhoto['public_id'],
                        'expiration_date' => $request->vehicle_insurance_expiration_date,
                        'status' => DocumentStatus::PENDING,
                    ]
                );
            }

            DB::commit();

            return $this->success(null, "Documents updated successfully", 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    public function updateUnion($request)
    {
        $user = User::with('documents')->findOrFail($request->user_id);

        $user->update([
            'transit_company_union_id' => $request->transit_company_union_id
        ]);

        return $this->success(null, "Union updated successfully", 200);
    }

}

