<?php

namespace App\Http\Resources;

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
            'amount_paid' => $this->amount_paid,
            'status' => $this->status,
            'payment_status' => $this->payment_status,
            'payment_method' => $this->payment_method,
            'trip_detail' => (object)[
                'departure_id' => $this->trip?->departure,
                'destination_id' => $this->trip?->destination,
                'departure' => $this->trip?->departureRegion?->state?->name . ' > ' . $this->trip?->departureRegion?->name,
                'departure_park' => $this->trip?->departureRegion?->parks->pluck('name')->join(', '),
                'destination' => $this->trip?->destinationRegion?->state?->name . ' > ' . $this->trip?->destinationRegion?->name,
                'destination_park' => $this->trip?->destinationRegion?->parks->pluck('name')->join(', '),
                'departure_date' => $this->trip?->departure_date,
                'departure_time' => $this->trip?->departure_time,
                'trip_duration' => $this->trip?->trip_duration,
                'estimated_arrival_time' => $this->trip?->trip_duration,
            ],
            'user_detail' => (object)[
                'first_name' => $this->user?->first_name,
                'last_name' => $this->user?->last_name,
            ],
            'vehicle_detail' => (object)[
                'name' => $this->trip?->vehicle?->model,
                'plate_number' => $this->trip?->vehicle?->plate_no,
                'type' => $this->trip?->vehicle?->type,
                'capacity' => $this->trip?->vehicle?->capacity,
                'color' => $this->trip?->vehicle?->color,
                'year' => $this->trip?->vehicle?->year,
                'seats' => $this->trip?->vehicle?->seats,
            ],
            'transit_company_detail' => (object)[
                'name' => $this->user?->transitCompany?->name,
                'reg_no' => $this->user?->transitCompany?->reg_no,
                'address' => $this->user?->transitCompany?->address,
                'phone' => $this->user?->transitCompany?->phone,
                'email' => $this->user?->transitCompany?->email,
                'park' => $this->user?->transitCompany?->park,
                'type' => $this->user?->transitCompany?->type,
            ],
        ];
    }
}
