<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Manifest extends Model
{
    protected $fillable = [
        'trip_id',
        'status',
    ];

    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }
}
