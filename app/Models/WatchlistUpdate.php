<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WatchlistUpdate extends Model
{
    protected $fillable = [
        'observation',
        'state_id',
        'city',
        'watchlist_id',
    ];
}
