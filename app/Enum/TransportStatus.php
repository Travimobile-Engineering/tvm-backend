<?php

namespace App\Enum;

enum TransportStatus: string
{
    const ACTIVE = "active";
    const COMPLETED = "completed";
    const INPROGRESS = "in-progress";
    const CANCELLED = "cancelled";
}
