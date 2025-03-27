<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
Broadcast::channel('trip.created.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
Broadcast::channel('trip.departure.{tripId}', function (User $user, $tripId) {
    return $user->trips()->where('id', $tripId)->exists();
});
Broadcast::channel('trip.start.{tripId}', function (User $user, $tripId) {
    return $user->trips()->where('id', $tripId)->exists();
});
Broadcast::channel('wallet.funded.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});
Broadcast::channel('trip.booking.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});
Broadcast::channel('trip.cancelled.{tripId}', function (User $user, $tripId) {
    return $user->trips()->where('id', $tripId)->exists();
});
Broadcast::channel('booking.cancelled.{bookingId}', function ($user, $bookingId) {
    return $user->bookings()->where('id', $bookingId)->exists();
});
