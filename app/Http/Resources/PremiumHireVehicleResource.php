<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PremiumHireVehicleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $distance = (float) $request->query('distance', 0);

        return [
            'id' => $this->id,
            'vehicle_id' => $this->vehicle?->id,
            'vehicle_model' => $this->vehicle?->model,
            'company_logo' => $this->user?->profile_photo,
            'ac' => $this->vehicle?->ac,
            'description' => $this->vehicle?->description,
            'amount' => $distance * 10.00,
            'seats' => is_array($seats = $this->vehicle?->seats) ? count($seats) : 0,
            'interior_images' => $this->vehicle?->vehicleImages()
                ->where('type', 'interior')
                ->get()
                ->pluck('url'),
            'exterior_images' => $this->vehicle?->vehicleImages()
                ->where('type', 'exterior')
                ->get()
                ->pluck('url'),
            'rating' => $this->vehicle?->premiumHireRatings?->avg('rating') ?? 0,
        ];
    }
}
