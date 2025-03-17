<?php

namespace App\Http\Resources;

use App\Enum\ManifestStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TripResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $filteredBookings = $this->tripBookings->where('payment_status', 1);

        $seats = $this->vehicle?->seats;
        $totalSeats = is_array($seats) ? count($seats) : 0;
        $totalSelectedSeats = $filteredBookings ? $filteredBookings->count() : 0;
        $availableSeats = $totalSeats - $totalSelectedSeats;

        $selected_seats = $filteredBookings ? explode(",",implode(",", $this->tripBookings->map(function ($passenger) {
            return str_replace(["[", "]", "\""], "", $passenger->selected_seat);
        })->toArray())) : [];

        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'user_id' => $this->user_id,
            'user' => (object)[
                'first_name' => $this->user?->first_name,
                'last_name' => $this->user?->last_name,
                'profile_photo' => $this->user?->profile_photo,
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
                'seats' => $this->vehicle?->seats,
                'seat_row' => $this->vehicle?->seat_row,
                'seat_column' => $this->vehicle?->seat_column
            ],
            'departure_id' => $this->departure,
            'destination_id' => $this->destination,
            'departure' => $this->departureRegion?->state?->name . ' > ' . $this->departureRegion?->name,
            'departure_park' => $this->departureRegion?->parks->pluck('name')->join(', '),
            'destination' => $this->destinationRegion?->state?->name . ' > ' . $this->destinationRegion?->name,
            'destination_park' => $this->destinationRegion?->parks->pluck('name')->join(', '),
            'departure_date' => $this->departure_date,
            'departure_time' => $this->departure_time,
            'trip_duration' => $this->trip_duration,
            'estimated_arrival_time' => $this->trip_duration,
            'trip_days' => $this->trip_days,
            'reoccur_duration' => $this->reoccur_duration,
            'bus_type' => $this->bus_type,
            'price' => $this->price,
            'park' => $this->transitCompany?->park,
            'bus_stops' => $this->bus_stops,
            'type' => $this->type,
            'status' => $this->status,
            'manifest_status' => $this->manifest?->status,
            'reason' => $this->reason,
            'date_cancelled' => $this->date_cancelled,
            'created_at' => $this->created_at,
            'passengers' => $filteredBookings ? $filteredBookings->map(function ($passenger) {
                return [
                    'id' => $passenger?->user?->id,
                    'first_name' => $passenger?->user?->first_name . ' ' . $passenger?->user?->last_name,
                    'phone__number' => $passenger?->user?->phone_number,
                    'next_of_kin' => $passenger?->user?->next_of_kin_full_name,
                    'next_of_kin_phone' => $passenger?->user?->next_of_kin_phone_number,
                    'booking_id' => $passenger?->booking_id,
                    'seat' => $passenger?->selected_seat,
                    'on_seat' => $passenger?->on_seat,
                ];
            })->toArray() : [],
            'selected_seats' => $filteredBookings ? $filteredBookings->map(function ($passenger) {
                return $passenger?->selected_seat;
            })->flatMap(function ($seat) {
                return explode(',', str_replace('"', '', $seat));
            })->unique()->values()->toArray() : [],
            'total_selected_seats' => $filteredBookings ? $filteredBookings->count() : 0,
            'total_seat' => is_array($seats = $this->vehicle?->seats) ? count($seats) : 0,
            'available_seat_count' => $availableSeats,
            'available_seats' => collect($this->vehicle?->seats)->filter(fn($seat) => !in_array($seat, $selected_seats))->values(),
            'manifest_fee' => 1000,
        ];
    }
}
