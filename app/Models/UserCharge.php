<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserCharge extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'date',
        'amount',
        'user_category',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
