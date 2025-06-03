<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class Fee extends Model
{
    protected $table = "fees";

    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn (string $value) => strtolower($value),
        );
    }
}
