<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NpisEvent extends Model
{
    protected $fillable = [
        'rank',
        'first_name',
        'last_name',
        'phone_number',
        'email',
    ];
}
