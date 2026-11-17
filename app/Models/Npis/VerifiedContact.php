<?php

namespace App\Models\Npis;

use App\Casts\EncryptedAttribute;
use App\Support\Npis\Destination;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class VerifiedContact extends Model
{
    protected $table = 'npis_verified_contacts';

    protected $primaryKey = 'id';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'channel',
        'destination',
        'destination_hash',
        'verified_at',
        'last_verified_at',
    ];

    protected $hidden = ['destination_hash'];

    protected function casts(): array
    {
        return [
            'destination' => EncryptedAttribute::class,
            'verified_at' => 'datetime',
            'last_verified_at' => 'datetime',
        ];
    }

    public function registration(): HasOne
    {
        return $this->hasOne(Registration::class, 'verified_contact_id');
    }

    public static function findByDestination(string $channel, string $destination): ?self
    {
        return self::query()
            ->where('channel', $channel)
            ->where('destination_hash', Destination::hash($destination))
            ->first();
    }

    public static function firstOrCreateFor(string $channel, string $destination): self
    {
        $normalized = Destination::normalize($channel, $destination);

        return self::query()->firstOrCreate(
            ['channel' => $channel, 'destination_hash' => Destination::hash($normalized)],
            [
                'id' => 'con_'.strtolower((string) str()->ulid()),
                'destination' => $normalized,
            ]
        );
    }
}
