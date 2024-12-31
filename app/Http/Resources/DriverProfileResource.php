<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DriverProfileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (int)$this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'phone_number' => $this->phone_number,
            'profile_picture' => $this->profile_picture,
            'wallet' => $this->wallet,
            'address' => $this->address,
            'gender' => $this->gender,
            'is_admin' => $this->is_admin,
            'next_of_kin_full_name' => $this->next_of_kin_full_name,
            'next_of_kin_phone_number' => $this->next_of_kin_phone_number,
            'next_of_kin_gender' => $this->next_of_kin_gender,
            'avatar_url' => $this->avatar_url,
            'profile_photo' => $this->profile_photo,
            'status' => ($this->email_verified || $this->sms_verified) ? 'verified' : 'pending',
            'transit_company' => (object)[
                'id' => $this->transitCompany?->id,
                'name' => $this->transitCompany?->name,
                'email' => $this->transitCompany?->email,
                'address' => $this->transitCompany?->address,
            ],
            'vehicle' => (object)[
                'id' => (int)$this->driverVehicle?->id,
                'year' => $this->driverVehicle?->vehicle_year,
                'model' => $this->driverVehicle?->vehicle_model,
                'color' => $this->driverVehicle?->vehicle_color,
                'type' => $this->driverVehicle?->vehicle_type,
                'capacity' => $this->driverVehicle?->vehicle_capacity,
                'plate_number' => $this->driverVehicle?->plate_number,
                'seats' => json_decode($this->driverVehicle?->seats),
                'seat_row' => $this->driverVehicle?->seat_row,
                'seat_column' => $this->driverVehicle?->seat_column
            ],
            'documents' => $this->documents ? $this->documents->map(function($document) {
                return [
                    'id' => (int)$document->id,
                    'type' => $document->type,
                    'image_url' => $document->image_url,
                    'number' => $document->number,
                    'expiration_date' => $document->expiration_date,
                    'status' => $document->status,
                ];
            })->toArray() : [],
        ];
    }
}
