<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $airline_id
 * @property string $name
 * @property string $environment
 * @property string $public_key
 * @property string $secret_key_hash
 * @property string $secret_key_prefix
 * @property string $secret_key_hint
 * @property bool $is_active
 * @property Carbon|null $last_used_at
 * @property string|null $last_used_ip
 * @property int $request_count
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder|AirlineApiKey active()
 * @method static \Illuminate\Database\Eloquent\Builder|AirlineApiKey forEnvironment(string $env)
 */
class AirlineApiKey extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'airline_id',
        'name',
        'environment',
        'public_key',
        'secret_key_hash',
        'secret_key_prefix',
        'secret_key_hint',
        'is_active',
        'last_used_at',
        'last_used_ip',
        'request_count',
    ];

    protected $hidden = [
        'secret_key_hash',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_used_at' => 'datetime',
        ];
    }

    public function airline(): BelongsTo
    {
        return $this->belongsTo(Airline::class);
    }

    /**
     * Masked secret for display: sk_test_••••••Xk2a
     */
    public function getMaskedSecretAttribute(): string
    {
        return "{$this->secret_key_prefix} '******' {$this->secret_key_hint}";
    }

    /**
     * Verify a raw secret key against the stored hash.
     */
    public function verifySecret(string $rawSecret): bool
    {
        return password_verify($rawSecret, $this->secret_key_hash);
    }

    public function recordUsage(string $ip): void
    {
        $this->updateQuietly([
            'last_used_at' => now(),
            'last_used_ip' => $ip,
            'request_count' => $this->request_count + 1,
        ]);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForEnvironment($query, string $env)
    {
        return $query->where('environment', $env);
    }
}
