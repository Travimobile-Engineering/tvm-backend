<?php

namespace App\Enum;

enum ManifestStatus: string
{
    const COMPLETED = 'completed';

    const INPROGRESS = 'in-progress';

    const PENDING = 'pending';
}
