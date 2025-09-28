<?php

namespace App\Trait;

use Illuminate\Support\Facades\Crypt;

trait EncryptsIds
{
    protected function encryptIdKeys(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->encryptIdKeys($value);

                continue;
            }

            if ($this->isEncryptableIdKey($key, $value)) {
                $data[$key] = Crypt::encryptString((string) $value);
            }
        }

        return $data;
    }

    protected function isEncryptableIdKey(string $key, $value): bool
    {
        if ($value === null || $value === '') {
            return false;
        }

        // exact keys you want encrypted
        static $exact = [
            'id', 'user_id', 'driver_id', 'vehicle_id', 'booking_id',
            'trip_id', 'notification_id', 'classification_id', 'state_id',
            'zone_id', 'owner_id', 'security_question_id', 'transit_company_union_id', 'agent_id',
        ];

        if (in_array($key, $exact, true)) {
            return true;
        }

        // catch-all for *_id
        return str_ends_with($key, '_id');
    }
}
