<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PremiumHireBookingResource extends JsonResource
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
            'vehicle' => (object)[
                'id' => $this->vehicle_id,
                'model' => $this->vehicle->model,
                'image' => $this->vehicle->vehicleImages()->value('url'),
            ],
            'number_of_vehicles' => $this->number_of_vehicles,
            'amount' => $this->amount,
            'ticket_type' => $this->ticket_type,
            'date' => $this->date,
            'departure_id' => $this->departure_id,
            'pickup_location' => $this->departureRegion?->state?->name . ', ' . $this->departureRegion?->name,
            'destination_id' => $this->destination_id,
            'dropoff_location' => $this->destinationRegion?->state?->name . ', ' . $this->destinationRegion?->name,
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
            'booking_timeline' => (object)[
                'request_hire_vehicle' => $this->resource ? 'Completed' : 'Pending',
                'payment_verification' => ($this->resource && $this->resource->payment_status === 'success') ? 'Completed' : 'Pending',
                'driver_start_trip' => ($this->resource && $this->resource->status === 'in-progress') ? 'In Progress' : 'Pending',
                'driver_end_trip' => ($this->resource && $this->resource->status === 'completed') ? 'Completed' : 'Pending',
            ],
            'reason' => $this->reason,
            'cancelled_on' => $this->reason ? $this->updated_at->format('j F, Y') : null,
            'status' => $this->status
        ];
    }
}
