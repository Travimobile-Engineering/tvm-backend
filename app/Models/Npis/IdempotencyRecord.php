<?php

namespace App\Models\Npis;

use App\Casts\EncryptedArray;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $scope
 * @property string $idempotency_key
 * @property string $method
 * @property string $path
 * @property string $request_hash
 * @property int $status_code
 * @property array<string, mixed>|null $response_body
 * @property Carbon $expires_at
 */
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
