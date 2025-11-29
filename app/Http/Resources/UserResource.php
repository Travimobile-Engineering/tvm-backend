<?php

namespace App\Http\Resources;

use App\Trait\EncryptsIds;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    use EncryptsIds;

    public function toArray($request)
    {
        // Start from full model array (or select specific fields if you prefer)
        $data = parent::toArray($request);

        // Encrypt id / *_id keys
        $data = $this->encryptIdKeys($data);

        return $data;
    }
}
