<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AirlineManifestResource extends JsonResource
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
            'manifest_number' => $this->manifest_number,
            'status' => $this->status,
            'source' => $this->source,
            'airline' => [
                'id' => $this->airline->id,
                'name' => $this->airline->name,
            ],
            'routing' => $this->routing,
            'standby_location' => $this->standby_location,
            'aircraft_type' => $this->aircraft_type,
            'aircraft_registration' => $this->aircraft_registration,
            'customer' => $this->customer,
            'planned_departure_time' => $this->planned_departure_time?->format('Y-m-d H:i'),
            'flight_date' => $this->flight_date?->format('Y-m-d'),
            'payload' => [
                'total_bag_pcs' => $this->total_bag_pcs,
                'total_bag_wt' => (float) $this->total_bag_wt,
                'total_pax_count' => $this->total_pax_count,
                'total_pax_wt' => (float) $this->total_pax_wt,
                'total_cargo_pcs' => $this->total_cargo_pcs,
                'total_cargo_wt' => (float) $this->total_cargo_wt,
                'total_payload_wt' => (float) $this->total_payload_wt,
                'unit' => 'KGS',
            ],
            'client_rep_name' => $this->client_rep_name,
            'time_received' => $this->time_received,
            'time_closed' => $this->time_closed,
            'reason_for_delay' => $this->reason_for_delay,
            'crews' => AirlineManifestCrewResource::collection($this->whenLoaded('crews')),
            'passengers' => AirlineManifestPassengerResource::collection($this->whenLoaded('passengers')),
            'cargos' => AirlineManifestCargoResource::collection($this->whenLoaded('cargos')),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
