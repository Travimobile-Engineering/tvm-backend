<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BusStopResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (int)$this->id,
            'state_id' => $this->state_id,
            'state' => $this->state->name,
            'stops' => $this->stops,
            'stop_count' => count($this->stops),
        ];
    }
}
