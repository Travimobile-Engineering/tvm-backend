<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PassengerProfileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return[
            'id' => (int)$this->id,
            'uuid' => $this->uuid,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'phone_number' => $this->phone_number,
            'wallet' => (float)$this->wallet_amount,
            'address' => $this->address,
            'gender' => $this->gender,
            'is_admin' => $this->is_admin,
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
            'wallet_setup' => hasSetupWallet($this->id),
            'pin_setup' => hasSetupPin($this->id),
            'wallet_info' => (object)[
                'available_balance' => (float)$this->wallet_amount,
            ],
            'bank' => (object)[
                'id' => $this->userBank?->id,
                'account_name' => $this->userBank?->account_name,
                'account_number' => $this->userBank?->account_number,
                'bank_name' => $this->userBank?->bank_name,
            ],
            'has_setup_security_answer' => hasSetSecurityAnswer($this->id),
            'security_question' => $this->securityQuestion?->question,
        ];
    }
}
