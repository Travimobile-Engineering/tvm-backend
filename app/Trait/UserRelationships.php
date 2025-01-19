<?php

namespace App\Trait;

use App\Models\BusStop;
use App\Models\Document;
use App\Models\DriverBank;
use App\Models\DriverPin;
use App\Models\DriverVehicle;
use App\Models\PaymentLog;
use App\Models\Transaction;
use App\Models\TransitCompany;
use App\Models\Trip;
use App\Models\TripBooking;
use App\Models\TripPayment;
use App\Models\UserTransferReceipient;
use App\Models\UserWithdrawLog;
use App\Models\Vehicle\Vehicle;

trait UserRelationships
{
    public function trips()
    {
        return $this->hasMany(Trip::class, 'user_id');
    }

    public function tripBookings()
    {
        return $this->hasMany(TripBooking::class, 'user_id');
    }

    public function transitCompany()
    {
        return $this->hasOne(TransitCompany::class, 'user_id');
    }

    public function vehicle()
    {
        return $this->hasOne(Vehicle::class, 'user_id');
    }

    public function documents()
    {
        return $this->hasMany(Document::class, 'user_id');
    }

    public function busStops()
    {
        return $this->hasMany(BusStop::class, 'user_id');
    }

    public function driverBank()
    {
        return $this->hasOne(DriverBank::class, 'user_id');
    }

    public function driverPin()
    {
        return $this->hasOne(DriverPin::class, 'user_id');
    }

    public function userTransferReceipient()
    {
        return $this->hasOne(UserTransferReceipient::class, 'user_id');
    }

    public function userWithdrawLogs()
    {
        return $this->hasMany(UserWithdrawLog::class, 'user_id');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'user_id');
    }

    public function userTripPayments()
    {
        return $this->hasMany(TripPayment::class, 'user_id');
    }

    public function driverTripPayments()
    {
        return $this->hasMany(TripPayment::class, 'driver_id', 'id');
    }

    public function paymentLogs()
    {
        return $this->hasMany(PaymentLog::class, 'user_id');
    }
}




