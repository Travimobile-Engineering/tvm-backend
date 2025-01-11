<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;

class TripResource extends JsonResource
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
            'uuid' => $this->uuid,
            'user_id' => $this->user_id,
            'user' => (object)[
                'first_name' => $this->user?->first_name,
                'last_name' => $this->user?->last_name,
            ],
            'vehicle_id' => $this->vehicle_id,
            'departure_id' => $this->departure,
            'destination_id' => $this->destination,
            'departure' => $this->departureRegion?->state?->name . ' > ' . $this->departureRegion?->name,
            'destination' => $this->destinationRegion?->state?->name . ' > ' . $this->destinationRegion?->name,
            'departure_time' => $this->departure_time,
            'trip_duration' => $this->trip_duration,
            'start_date' => $this->type == 'one-time' ? $this->departure_date : $this->start_date,
            'trip_days' => $this->trip_days,
            'reoccur_duration' => $this->reoccur_duration,
            'bus_type' => $this->bus_type,
            'price' => $this->price,
            'bus_stops' => $this->bus_stops,
            'type' => $this->type,
            'status' => $this->status,
            'manifest_status' => $this->manifests->count() == 0 ? 'processing' : 'created',
            'reason' => $this->reason,
            'date_cancelled' => $this->date_cancelled,
            'created_at' => $this->created_at,
            'passengers' => $this->tripBookings ? $this->tripBookings->map(function ($passenger) {
                return [
                    'id' => $passenger?->user?->id,
                    'first_name' => $passenger?->user?->first_name,
                    'booking_id' => $passenger?->booking_id,
                    'seat' => (int)$passenger?->selected_seat,
                ];
            })->toArray() : [],
            'selected_seats' => $this->tripBookings ? explode(',', str_replace(['[',']','"'], '', implode(',',$this->tripBookings->map(function ($passenger) {
                return $passenger->selected_seat;
            })->toArray()))) : [],
            'total_selected_seats' => $this->tripBookings ? $this->tripBookings->count() : 0,
            'available_seats' => collect(json_decode($this->vehicle->seats))->filter(fn($item) => !in_array($item, explode(',', str_replace(['[',']','"'], '', implode(',',$this->tripBookings->map(function ($passenger) {
                return $passenger->selected_seat;
            })->toArray())))))->toArray()
        ];
    }
}
