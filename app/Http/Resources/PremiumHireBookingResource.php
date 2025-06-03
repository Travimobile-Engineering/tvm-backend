<?php

namespace App\Http\Resources;

use Carbon\Carbon;
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
            'driver' => (object)[
                'id' => $this->driver_id,
                'first_name' => $this->driver?->first_name,
                'last_name' => $this->driver?->last_name,
            ],
            'vehicle' => (object)[
                'id' => $this->vehicle_id,
                'model' => $this->vehicle?->model,
                'image' => $this->vehicle?->vehicleImages()->value('url'),
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
            'booking_timeline' => (object)[
                'request' => (object)[
                    'request_hire_vehicle' => $this->resource ? 'Completed' : 'Pending',
                    'request_date' => Carbon::parse($this->created_at)->format('Y-m-d H:i:s') ,
                ],
                'payment' => (object)[
                    'payment_verification' => ($this->resource && $this->resource->payment_status === 'success') ? 'Completed' : 'Pending',
                    'payment_verification_date' => $this->paymentLog ? Carbon::parse($this->paymentLog->created_at)
                        ->format('Y-m-d H:i:s') : null,
                ],
                'start_trip' => (object)[
                    'driver_start_trip' => ($this->resource && $this->resource->status === 'in-progress') ? 'In Progress' : 'Pending',
                    'driver_start_trip_date' => ($this->resource && $this->resource->start_trip_date)
                        ? Carbon::parse($this->resource->start_trip_date)->format('Y-m-d H:i:s')
                        : null,
                ],
                'end_trip' => (object)[
                    'driver_end_trip' => ($this->resource && $this->resource->status === 'completed') ? 'Completed' : 'Pending',
                    'driver_end_trip_date' => ($this->resource && $this->resource->end_trip_date)
                        ? Carbon::parse($this->resource->end_trip_date)->format('Y-m-d H:i:s')
                        : null,
                ],
            ],
            'manifest_fee' => getFee('manifest'),
            'reason' => $this->reason,
            'cancelled_on' => $this->reason ? $this->updated_at : null,
            'status' => $this->status
        ];
    }
}
