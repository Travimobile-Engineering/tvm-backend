<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentLog extends Model
{
    protected $fillable = [
        'user_id',
        'trip_id',
        'first_name',
        'last_name',
        'email',
        'phone_number',
        'amount',
        'reference',
        'channel',
        'currency',
        'ip_address',
        'paid_at',
        'createdAt',
        'transaction_date',
        'status',
        'type',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }
}
