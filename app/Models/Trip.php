<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Trip extends Model
{
    protected $guarded = [];
    protected $hidden = ['id', 'departure', 'destination'];
    public function getRouteKeyName(){
        return 'trip_id';
    }
}
