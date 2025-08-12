<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Crypt;

abstract class BaseResource extends JsonResource
{
    protected function encId($id): string
    {
        return Crypt::encryptString((string) $id);
    }
}


