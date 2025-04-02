<?php

namespace App\Trait;

use App\Models\BusStop;
use App\Models\Document;
use App\Models\Notification;
use App\Models\PaymentLog;
use App\Models\PremiumHireBooking;
use App\Models\PremiumHireBookingPassenger;
use App\Models\PremiumHireManifest;
use App\Models\PremiumHireRating;
use App\Models\PremiumUpgrade;
use App\Models\Transaction;
use App\Models\TransitCompany;
use App\Models\Trip;
use App\Models\TripBooking;
use App\Models\TripPayment;
use App\Models\UnavailableDate;
use App\Models\UserBank;
use App\Models\UserPin;
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

    public function agentTripBookings()
    {
        return $this->hasMany(TripBooking::class, 'user_id', 'agent_id');
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

    public function userBank()
    {
        return $this->hasOne(UserBank::class, 'user_id');
    }

    public function userPin()
    {
        return $this->hasOne(UserPin::class, 'user_id');
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

    public function premiumUpgrades()
    {
        return $this->hasMany(PremiumUpgrade::class, 'user_id');
    }

    public function unavailableDates()
    {
        return $this->hasMany(UnavailableDate::class, 'user_id');
    }

    public function premiumHireBookings()
    {
        return $this->hasMany(PremiumHireBooking::class, 'user_id');
    }

    public function driverPremiumHireBookings()
    {
        return $this->hasMany(PremiumHireBooking::class, 'driver_id');
    }

    public function premiumHireBookingPassengers()
    {
        return $this->hasMany(PremiumHireBookingPassenger::class, 'user_id');
    }

    public function premiumHireManifests()
    {
        return $this->hasMany(PremiumHireManifest::class, 'user_id');
    }

    public function premiumHireRatings()
    {
        return $this->hasMany(PremiumHireRating::class, 'user_id');
    }

    public function userNotifications()
    {
        return $this->hasMany(Notification::class, 'user_id');
    }
}




