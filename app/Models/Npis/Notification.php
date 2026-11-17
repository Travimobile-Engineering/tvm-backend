<?php

namespace App\Models\Npis;

use App\Casts\EncryptedAttribute;
use App\Support\Npis\Destination;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $table = 'npis_notifications';

    protected $primaryKey = 'id';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'template_key',
        'channel',
        'recipient',
        'recipient_hash',
        'subject',
        'body',
        'status',
        'provider',
        'provider_message_id',
        'provider_response',
        'related_type',
        'related_id',
        'sent_at',
        'delivered_at',
        'failed_at',
        'error',
    ];

    protected $hidden = ['recipient', 'recipient_hash', 'body'];

    protected function casts(): array
    {
        return [
            'recipient' => EncryptedAttribute::class,
            'body' => EncryptedAttribute::class,
            'provider_response' => 'array',
            'sent_at' => 'datetime',
            'delivered_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    public static function makeRecipientHash(string $channel, string $recipient): string
    {
        return Destination::hash(Destination::normalize($channel, $recipient));
    }
}
