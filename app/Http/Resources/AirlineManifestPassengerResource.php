<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AirlineManifestPassengerResource extends JsonResource
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
            'row_number' => $this->row_number,
            'name' => $this->name,
            'job' => $this->job,
            'company' => $this->company,
            'bag_pcs' => $this->bag_pcs,
            'bag_wt' => (float) $this->bag_wt,
            'pax_wt' => (float) $this->pax_wt,
            'total_wt' => (float) $this->total_wt,
            'from_location' => $this->from_location,
            'to_location' => $this->to_location,
            'is_special_cargo' => $this->is_special_cargo,
            'special_cargo_type' => $this->special_cargo_type,
        ];
    }
}
