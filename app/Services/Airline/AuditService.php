<?php

namespace App\Services\Airline;

use App\Models\Airline;
use App\Models\AirlineAuditLog;

class AuditService
{
    public function log(Airline $airline, string $event, array $meta = [], ?int $apiKeyId = null): void
    {
        AirlineAuditLog::create([
            'airline_id' => $airline->id,
            'api_key_id' => $apiKeyId,
            'event' => $event,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'meta' => $meta,
        ]);
    }
}
