<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AirlineManifestCargoResource extends JsonResource
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
            'description' => $this->description,
            'pcs' => $this->pcs,
            'company' => $this->company,
            'weight' => (float) $this->weight,
            'from_location' => $this->from_location,
            'to_location' => $this->to_location,
        ];
    }
}
