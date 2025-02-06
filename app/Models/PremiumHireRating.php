<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PremiumHireRating extends Model
{
    protected $fillable = [
        'user_id',
        'rating',
        'comment',
        'status'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
