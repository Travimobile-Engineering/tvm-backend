<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JobOpeningResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request) : array
    {
        return [
            "id" => $this->id,
            "title" => $this->title,
            "type" => $this->type,
            "deadline" => $this->deadline,
            "summary" => $this->summary,
            "responsibilities" => $this->responsibilities,
            "requirement" => $this->requirement,
            "offer" => $this->offer,
        ];
    }
}
