<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class TripBooking extends Model
{
    use Notifiable;

    protected $fillable = [
        'payment_log_id',
        'booking_id',
        'user_id',
        'trip_id',
        'agent_id',
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
        'receive_sms',
        'on_seat',
    ];

    protected $hidden = ['id'];

    protected function casts(): array
    {
        return [
            'travelling_with' => 'array',
            'third_party_passenger_details' => 'array',
            'receive_sms' => 'boolean',
            'on_seat' => 'boolean',
            'selected_seat' => 'array',
        ];
    }

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

    public function tripBookingPassengers()
    {
        return $this->hasMany(TripBookingPassenger::class);
    }

    // Attributes
    protected function totalPassengers(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->tripBookingPassengers()->count() ?? 0,
        );
    }
}
