<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TripBooking extends Model
{
    protected $fillable = [
        'payment_log_id',
        'booking_id',
        'user_id',
        'trip_id',
        'third_party_booking',
        'selected_seat',
        'trip_type',
        'travelling_with',
        'third_party_passenger_details',
        'amount_paid',
        'status',
        'payment_status',
        'payment_method',
        'manifest_status',
    ];

    protected $hidden = ['id'];

    public function getRouteKeyName()
    {
        return 'booking_id';
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function trip()
    {
        return $this->belongsTo(Trip::class, 'trip_id');
    }

    public function paymentLog()
    {
        return $this->belongsTo(PaymentLog::class, 'payment_log_id');
    }
}
