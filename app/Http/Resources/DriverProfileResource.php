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
        $pendingBalance = $this->driverTripPayments->where('status', 'pending')
            ->sum('amount');

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
            'driver_verified' => $this->driver_verified,
            'transit_company' => (object)[
                'id' => $this->transitCompany?->id,
                'name' => $this->transitCompany?->name,
                'email' => $this->transitCompany?->email,
                'address' => $this->transitCompany?->address,
                'park' => $this->transitCompany?->park,
            ],
            'vehicle' => (object)[
                'id' => (int)$this->vehicle?->id,
                'year' => $this->vehicle?->year,
                'model' => $this->vehicle?->model,
                'color' => $this->vehicle?->color,
                'type' => $this->vehicle?->type,
                'capacity' => $this->vehicle?->capacity,
                'plate_number' => $this->vehicle?->plate_no,
                'seats' => $this->vehicle?->seats,
                'seat_row' => $this->vehicle?->seat_row,
                'seat_column' => $this->vehicle?->seat_column
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
            'wallet_setup' => hasSetupWallet($this->id),
            'wallet_info' => (object)[
                'available_balance' => $this->wallet,
                'pending_balance' => $pendingBalance,
            ],
        ];
    }
}
