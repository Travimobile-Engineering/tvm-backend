<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OneTimeTripResource extends JsonResource
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
            'user_id' => $this->user_id,
            'user' => (object)[
                'first_name' => $this->user->first_name,
                'last_name' => $this->user->last_name,
            ],
            'departure' => $this->departure,
            'destination' => $this->destination,
            'departure_date' => $this->departure_date,
            'departure_time' => $this->departure_time,
            'repeat_trip' => $this->repeat_trip,
            'bus_type' => $this->bus_type,
            'ticket_price' => $this->ticket_price,
            'bus_stops' => $this->bus_stops,
            // 'passengers' => $this->tripBookings ? $this->tripBookings->map(function ($passenger) {
            //     return [
            //         'id' => $passenger?->user?->id,
            //         'first_name' => $passenger?->user?->first_name,
            //         'last_name' => $passenger?->user?->last_name,
            //         'email' => $passenger?->user?->email,
            //         'phone_number' => $passenger?->user?->phone_number
            //     ];
            // })->toArray() : [],
            'type' => $this->type,
            'status' => $this->status,
            'reason' => $this->reason,
            'date_cancelled' => $this->date_cancelled,
            'created_at' => $this->created_at,
        ];
    }
}
