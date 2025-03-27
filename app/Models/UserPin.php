<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserPin extends Model
{
    protected $fillable = [
        'user_id',
        'pin',
        'ip_address',
        'device_info',
        'attempts',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
