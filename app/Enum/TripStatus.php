<?php

namespace App\Enum;

enum TripStatus: string
{
    const ACTIVE = "active";
    const ACCEPTED = "accepted";
    const COMPLETED = "completed";
    const INPROGRESS = "in-progress";
    const CANCELLED = "cancelled";
    const UPCOMING = "upcoming";
    const REQUEST = "request";
}
