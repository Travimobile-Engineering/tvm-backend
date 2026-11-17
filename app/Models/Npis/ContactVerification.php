<?php

namespace App\Models\Npis;

use App\Casts\EncryptedAttribute;
use App\Support\Npis\Destination;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string|null $contact_id
 * @property string $channel
 * @property string $destination
 * @property string $destination_hash
 * @property string $purpose
 * @property string $code_hash
 * @property Carbon $expires_at
 * @property Carbon $resend_available_at
 * @property int $resends_remaining
 * @property int $attempts_remaining
 * @property Carbon|null $consumed_at
 * @property string $delivery_status
 * @property string|null $ip
 * @property-read VerifiedContact|null $contact
 */
class ContactVerification extends Model
{
    protected $table = 'npis_contact_verifications';

    protected $primaryKey = 'id';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'contact_id',
        'channel',
        'destination',
        'destination_hash',
        'purpose',
        'code_hash',
        'expires_at',
        'resend_available_at',
        'resends_remaining',
        'attempts_remaining',
        'consumed_at',
        'delivery_status',
        'ip',
    ];

    protected $hidden = ['code_hash', 'destination_hash'];

    protected function casts(): array
    {
        return [
            'destination' => EncryptedAttribute::class,
            'expires_at' => 'datetime',
            'resend_available_at' => 'datetime',
            'consumed_at' => 'datetime',
            'resends_remaining' => 'integer',
            'attempts_remaining' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<VerifiedContact, $this>
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(VerifiedContact::class, 'contact_id');
    }

    public function isConsumed(): bool
    {
        return $this->consumed_at !== null;
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function maskedDestination(): string
    {
        return Destination::mask($this->channel, (string) $this->destination);
    }
}
