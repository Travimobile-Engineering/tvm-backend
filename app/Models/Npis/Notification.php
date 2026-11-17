<?php

namespace App\Models\Npis;

use App\Casts\EncryptedAttribute;
use App\Support\Npis\Destination;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $template_key
 * @property string $channel
 * @property string $recipient
 * @property string $recipient_hash
 * @property string|null $subject
 * @property string $body
 * @property string $status
 * @property string|null $provider
 * @property string|null $provider_message_id
 * @property array<string, mixed>|null $provider_response
 * @property string|null $related_type
 * @property string|null $related_id
 * @property Carbon|null $sent_at
 * @property Carbon|null $delivered_at
 * @property Carbon|null $failed_at
 * @property string|null $error
 */
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
