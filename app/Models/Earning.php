<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Earning extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'type',
        'amount',
        'description',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
