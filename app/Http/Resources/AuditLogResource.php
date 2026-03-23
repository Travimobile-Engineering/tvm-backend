<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuditLogResource extends JsonResource
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
            'event' => $this->event,
            'ip_address' => $this->ip_address,
            'user_agent' => $this->user_agent,
            'meta' => $this->meta,
            'api_key' => $this->whenLoaded('apiKey', fn () => [
                'id' => $this->apiKey->id,
                'name' => $this->apiKey->name,
                'environment' => $this->apiKey->environment,
            ]),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
