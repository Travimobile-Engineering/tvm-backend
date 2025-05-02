<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SecurityAgentProfileResource extends JsonResource
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
            'address' => $this->address,
            'gender' => $this->gender,
            'nin' => decryptData($this->nin),
            'next_of_kin_full_name' => $this->next_of_kin_full_name,
            'next_of_kin_phone_number' => $this->next_of_kin_phone_number,
            'next_of_kin_gender' => $this->next_of_kin_gender,
            'next_of_kin_relationship' => $this->next_of_kin_relationship,
            'avatar_url' => $this->avatar_url,
            'profile_photo' => $this->profile_photo,
            'status' => ($this->email_verified || $this->sms_verified) ? 'verified' : 'pending',
            'sms_notification' => $this->inbox_notifications,
            'email_notification' => $this->email_notifications,
        ];
    }
}
