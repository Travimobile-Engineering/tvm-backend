<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TripBookingResource extends JsonResource
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
            'booking_id' => $this->booking_id,
            'payment_log_id' => $this->payment_log_id,
            'user_id' => $this->user_id,
            'third_party_booking' => $this->third_party_booking,
            'selected_seat' => $this->selected_seat,
            'trip_type' => $this->trip_type,
            'travelling_with' => $this->travelling_with,
            'third_party_passenger_details' => $this->third_party_passenger_details,
            'trip_amount' => $this->amount_paid,
            'amount_paid' => $this->total_amount_paid,
            'charges' => $this->charges,
            'on_seat' => $this->on_seat,
            'status' => $this->status,
            'reason' => $this->reason,
            'date_canceled' => $this->date_canceled,
            'payment_status' => $this->payment_status,
            'payment_method' => $this->payment_method,
            'trip_detail' => (object)[
                'trip_id' => $this->trip?->id,
                'departure_id' => $this->trip?->departure,
                'destination_id' => $this->trip?->destination,
                'departure' => "{$this->trip?->departureRegion?->state?->name} > {$this->trip?->departureRegion?->name}",
                'departure_park' => $this->trip?->departureRegion?->parksWithTransitCompany->first()?->name,
                'destination' => "{$this->trip?->destinationRegion?->state?->name} > {$this->trip?->destinationRegion?->name}",
                'destination_park' => $this->trip?->destinationRegion?->parksWithTransitCompany->first()?->name,
                'departure_date' => $this->trip?->departure_date,
                'departure_time' => $this->trip?->departure_time,
                'trip_duration' => $this->trip?->trip_duration,
                'estimated_arrival_time' => $this->calculateEstimatedArrivalTime($this->trip?->departure_time, $this->trip?->trip_duration),
                'bus_stops' => $this->trip?->bus_stops,
                'status' => $this->trip?->status,
            ],
            'user_detail' => (object)[
                'first_name' => $this->user?->first_name,
                'last_name' => $this->user?->last_name,
            ],
            'driver_detail' => (object)[
                'first_name' => $this->trip?->user?->first_name,
                'last_name' => $this->trip?->user?->last_name,
                'phone_number' => $this->trip?->user?->phone_number,
                'profile_photo' => $this->trip?->user?->profile_photo,
            ],
            'vehicle_detail' => (object)[
                'name' => $this->trip?->vehicle?->model,
                'plate_number' => $this->trip?->vehicle?->plate_no,
                'type' => $this->trip?->vehicle?->type,
                'capacity' => $this->trip?->vehicle?->capacity,
                'color' => $this->trip?->vehicle?->color,
                'year' => $this->trip?->vehicle?->year,
                'seats' => $this->trip?->vehicle?->seats,
                'air_conditioned' => $this->trip?->vehicle?->air_conditioned,
            ],
            'transit_company_detail' => (object)[
                'name' => $this->user?->transitCompany?->name,
                'reg_no' => $this->user?->transitCompany?->reg_no,
                'address' => $this->user?->transitCompany?->address,
                'phone' => $this->user?->transitCompany?->phone,
                'email' => $this->user?->transitCompany?->email,
                'park' => $this->user?->transitCompany?->park,
                'type' => $this->user?->transitCompany?->type,
            ]
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
