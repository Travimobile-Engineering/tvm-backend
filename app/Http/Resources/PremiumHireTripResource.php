<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PremiumHireTripResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user' => (object) [
                'id' => $this->user?->id,
                'name' => $this->user?->first_name.' '.$this->user?->last_name,
                'email' => $this->user?->email,
                'phone_number' => $this->user?->phone_number,
            ],
            'vehicle' => (object) [
                'id' => $this->vehicle_id,
                'model' => $this->vehicle->model,
                'image' => $this->vehicle->vehicleImages()->value('url'),
            ],
            'amount' => $this->amount,
            'ticket_type' => $this->ticket_type,
            'time' => $this->time,
            'date' => $this->date,
            'lng' => $this->lng,
            'lat' => $this->lat,
            'pickup_location' => $this->pickup_location,
            'dropoff_location' => $this->dropoff_location,
            'bus_stops' => $this->bus_stops,
            'luggage' => $this->luggage,
            'passengers' => $this->premiumHireBookingPassengers ? $this->premiumHireBookingPassengers->map(function ($passenger) {
                return [
                    'id' => $passenger->id,
                    'name' => $passenger->name,
                    'email' => $passenger->email,
                    'phone_number' => $passenger->phone_number,
                    'next_of_kin' => $passenger->next_of_kin,
                    'next_of_kin_phone_number' => $passenger->next_of_kin_phone_number,
                ];
            })->toArray() : [],
            'passengers_count' => $this->premiumHireBookingPassengers ? $this->premiumHireBookingPassengers->count() : 0,
            'reason' => $this->reason,
            'cancelled_on' => $this->reason ? $this->updated_at->format('j F, Y') : null,
            'manifest_fee' => getFee('manifest'),
            'status' => $this->status,
        ];
    }
}
