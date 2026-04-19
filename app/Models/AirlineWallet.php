<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AirlineWallet extends Model
{
    protected $fillable = ['airline_id', 'environment', 'balance'];

    public function airline()
    {
        return $this->belongsTo(Airline::class);
    }
}
