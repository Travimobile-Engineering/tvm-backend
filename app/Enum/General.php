<?php

namespace App\Enum;

enum General: string
{
    const ACTIVE = 'active';

    const ACCEPTED = 'accepted';

    const COMPLETED = 'completed';

    const INPROGRESS = 'in-progress';

    const CANCELLED = 'cancelled';

    const UPCOMING = 'upcoming';

    const REQUEST = 'request';

    const PENDING = 'pending';

    const PROCESSING = 'processing';

    const FAILED = 'failed';

    const DRIVER_CHARGE = 'Driver charge';

    const PAID = 'paid';

    const REFUNDED = 'refunded';
}
