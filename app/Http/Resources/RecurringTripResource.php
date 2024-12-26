<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecurringTripResource extends JsonResource
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
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'trip_days' => $this->trip_days,
            'reoccur_duration' => $this->reoccur_duration,
            'bus_type' => $this->bus_type,
            'ticket_price' => $this->ticket_price,
            'bus_stops' => $this->bus_stops,
            'type' => $this->type,
            'status' => $this->status,
            'reason' => $this->reason,
            'date_cancelled' => $this->date_cancelled,
            'created_at' => $this->created_at,
        ];
    }
}
