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
            'uuid' => $this->uuid,
            'user_id' => $this->user_id,
            'user' => (object)[
                'first_name' => $this->user->first_name,
                'last_name' => $this->user->last_name,
            ],
            'vehicle_id' => $this->vehicle_id,
            'vehicle' => (object)[
                'name' => $this->vehicle?->name,
                'year' => $this->vehicle?->year,
                'model' => $this->vehicle?->model,
                'color' => $this->vehicle?->color,
                'type' => $this->vehicle?->type,
                'capacity' => $this->vehicle?->capacity,
                'plate_number' => $this->vehicle?->plate_no,
                'seats' => json_decode($this->vehicle?->seats) ?? $this->vehicle?->seats,
                'seat_row' => $this->vehicle?->seat_row,
                'seat_column' => $this->vehicle?->seat_column
            ],
            'departure_id' => $this->departure,
            'destination_id' => $this->destination,
            'departure' => $this->departureRegion?->state?->name . ' > ' . $this->departureRegion?->name,
            'destination' => $this->destinationRegion?->state?->name . ' > ' . $this->destinationRegion?->name,
            'departure_date' => $this->departure_date,
            'departure_time' => $this->departure_time,
            'trip_duration' => $this->trip_duration,
            'bus_type' => $this->bus_type,
            'price' => $this->price,
            'bus_stops' => $this->bus_stops,
            'means' => $this->means,
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
                    'last_name' => $passenger?->user?->last_name,
                    'booking_id' => $passenger?->booking_id,
                    'seat' => (int)$passenger?->selected_seat,
                ];
            })->toArray() : [],
            'selected_seats' => $this->tripBookings ? $this->tripBookings->map(function ($passenger) {
                return $passenger->selected_seat;
            })->toArray() : [],
            'total_selected_seats' => $this->tripBookings ? $this->tripBookings->count() : 0,
        ];
    }
}
