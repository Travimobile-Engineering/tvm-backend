<?php

namespace App\Http\Resources;

use Carbon\Carbon;
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
        $totalSelectedSeats = $this->tripBookings->sum(fn($passenger) => $passenger->total_passengers);
        $availableSeats = $totalSeats - $totalSelectedSeats;

        $selected_seats = $filteredBookings ? $this->tripBookings->flatMap(function ($passenger) {
            return $passenger->selected_seat;
        })->toArray() : [];

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
            'departure' => "{$this->departureRegion?->state?->name} > {$this->departureRegion?->name}",
            'departure_park' => $this->departureRegion?->parksWithTransitCompany->first()?->name,
            'destination' => "{$this->destinationRegion?->state?->name} > {$this->destinationRegion?->name}",
            'destination_park' => $this->destinationRegion?->parksWithTransitCompany->first()?->name,
            'departure_date' => $this->departure_date,
            'departure_time' => $this->departure_time,
            'trip_duration' => $this->trip_duration,
            'estimated_arrival_time' => $this->calculateEstimatedArrivalTime($this->departure_time, $this->trip_duration),
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
            'passengers' => $this->tripBookings->flatMap(fn($passenger) =>
                $passenger->tripBookingPassengers->map(fn($p) => [
                    'id' => $p->id,
                    'booking_id' => $passenger->booking_id,
                    'first_name' => $p->name,
                    'phone_number' => $p->phone_number,
                    'next_of_kin' => $p->next_of_kin,
                    'next_of_kin_phone' => $p->next_of_kin_phone_number,
                    'gender' => $p->gender,
                    'seat' => $p->selected_seat,
                    'on_seat' => $p->on_seat,
                ])
            )->values()->toArray(),
            'selected_seats' => $filteredBookings ? $filteredBookings->map(function ($passenger) {
                return $passenger?->selected_seat;
            })->flatMap(function ($seat) {
                return $seat;
            })->unique()->values()->toArray() : [],
            'total_selected_seats' => $this->tripBookings->sum(fn($passenger) => $passenger->total_passengers),
            'total_seat' => is_array($seats = $this->vehicle?->seats) ? count($seats) : 0,
            'available_seat_count' => $availableSeats,
            'available_seats' => collect($this->vehicle?->seats)->filter(fn($seat) => !in_array($seat, $selected_seats))->values(),
            'manifest_fee' => getFee('manifest'),
        ];
    }

    protected function calculateEstimatedArrivalTime($departureTime, $tripDuration): ?string
    {
        if (!$departureTime || !$tripDuration) {
            return null;
        }

        try {
            $departure = Carbon::createFromFormat('H:i', $departureTime);

            // Try to parse as "H:i" format first
            if (preg_match('/^\d{1,2}:\d{2}$/', $tripDuration)) {
                [$hours, $minutes] = explode(':', $tripDuration);
                $arrival = $departure->copy()->addHours((int)$hours)->addMinutes((int)$minutes);
                return $arrival->format('H:i');
            }

            // Normalize spacing and lowercase
            $duration = strtolower(preg_replace('/\s+/', '', $tripDuration));

            // Match patterns like "2hours30mins", "1hour", "45mins"
            preg_match('/(?:(\d+)hour[s]?)?(?:(\d+)min[s]?)?/', $duration, $matches);

            $hours = isset($matches[1]) ? (int)$matches[1] : 0;
            $minutes = isset($matches[2]) ? (int)$matches[2] : 0;

            $arrival = $departure->copy()->addHours($hours)->addMinutes($minutes);
            return $arrival->format('H:i');
        } catch (\Exception $e) {
            return null;
        }
    }
}
