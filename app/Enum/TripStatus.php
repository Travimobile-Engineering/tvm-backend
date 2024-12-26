<?php

namespace App\Enum;

enum TripStatus: string
{
    const ACTIVE = "active";
    const COMPLETED = "completed";
    const INPROGRESS = "in-progress";
    const CANCELLED = "cancelled";
}
