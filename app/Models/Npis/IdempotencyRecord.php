<?php

namespace App\Models\Npis;

use App\Casts\EncryptedArray;
use Illuminate\Database\Eloquent\Model;

class IdempotencyRecord extends Model
{
    protected $table = 'npis_idempotency_records';

    protected $fillable = [
        'scope',
        'idempotency_key',
        'method',
        'path',
        'request_hash',
        'status_code',
        'response_body',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'response_body' => EncryptedArray::class,
            'expires_at' => 'datetime',
        ];
    }
}
