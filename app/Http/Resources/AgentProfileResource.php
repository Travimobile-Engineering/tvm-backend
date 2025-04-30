<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AgentProfileResource extends JsonResource
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
            'wallet' => $this->wallet,
            'address' => $this->address,
            'gender' => $this->gender,
            'agent_id' => $this->agent_id,
            'nin' => decryptData($this->nin),
            'next_of_kin_full_name' => $this->next_of_kin_full_name,
            'next_of_kin_phone_number' => $this->next_of_kin_phone_number,
            'next_of_kin_gender' => $this->next_of_kin_gender,
            'next_of_kin_relationship' => $this->next_of_kin_relationship,
            'avatar_url' => $this->avatar_url,
            'profile_photo' => $this->profile_photo,
            'user_category' => $this->user_category,
            'status' => ($this->email_verified || $this->sms_verified) ? 'verified' : 'pending',
            'rating' => 3.5,
            'lng' => (float)$this->lng,
            'lat' => (float)$this->lat,
            'transit_company' => (object)[
                'id' => $this->transitCompany?->id,
                'name' => $this->transitCompany?->name,
                'email' => $this->transitCompany?->email,
                'address' => $this->transitCompany?->address,
                'park' => $this->transitCompany?->park,
            ],
            'bank' => (object)[
                'id' => $this->userBank?->id,
                'account_name' => $this->userBank?->account_name,
                'account_number' => $this->userBank?->account_number,
                'bank_name' => $this->userBank?->bank_name,
            ],
            'busstops' => BusStopResource::collection($this->busStops),
            'wallet_setup' => hasSetupWallet($this->id),
            'wallet_info' => (object)[
                'earnings' => (object) [
                    'available' => $this->wallet,
                ],
                'available_balance' => $this->wallet,
            ],
            'sms_notification' => $this->inbox_notifications,
            'email_notification' => $this->email_notifications,
        ];
    }
}
