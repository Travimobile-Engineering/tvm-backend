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
            'transit_company' => (object)[
                'id' => $this->transitCompany->id,
                'name' => $this->transitCompany->name,
                'email' => $this->transitCompany->email,
                'address' => $this->transitCompany->address,
            ]
        ];
    }
}
