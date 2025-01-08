<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DriverBank extends Model
{
    protected $fillable = [
        'user_id',
        'bank_name',
        'account_number',
        'account_name',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
