<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CharterResource extends JsonResource
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
            'vehicle_id' => $this->vehicle_id,
            'vehicle_model' => $this->vehicle->model,
            'ac' => $this->vehicle->ac,
            'number_of_vehicles' => $this->number_of_vehicles,
            'image' => $this->vehicle->vehicleImages()->value('url'),
            'amount' => 250000,
        ];
    }
}
