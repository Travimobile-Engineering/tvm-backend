<?php

namespace App\Models;

use App\Enum\EnvironmentType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $first_name
 * @property string $last_name
 * @property string $email
 * @property string $iata_code
 * @property string|null $country
 * @property string|null $website
 * @property string|null $logo_url
 * @property string $active_environment
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder|Airline active()
 */
class Airline extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'first_name',
        'last_name',
        'email',
        'iata_code',
        'country',
        'website',
        'logo_url',
        'active_environment',
        'is_active',
        'manifest_submission_method',
        'role',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'airline_id');
    }

    public function apiKeys(): HasMany
    {
        return $this->hasMany(AirlineApiKey::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AirlineAuditLog::class);
    }

    public function activeApiKeys(): HasMany
    {
        return $this->apiKeys()->where('is_active', true);
    }

    public function activeEnvironmentKeys(): HasMany
    {
        return $this->activeApiKeys()->where('environment', $this->active_environment);
    }

    public function wallets(): HasMany
    {
        return $this->hasMany(AirlineWallet::class);
    }

    public function manifests(): HasMany
    {
        return $this->hasMany(AirlineManifest::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(AirlineTransaction::class, 'airline_id');
    }

    public function isInProduction(): bool
    {
        return $this->active_environment === EnvironmentType::PRODUCTION->value;
    }

    public function isInTest(): bool
    {
        return $this->active_environment === EnvironmentType::TEST->value;
    }

    public function switchEnvironment(string $env): void
    {
        $this->update(['active_environment' => $env]);
    }

    public function currentWallet()
    {
        return $this->wallets()
            ->where('environment', $this->active_environment)
            ->first();
    }

    public function createTransaction($title, $amount, $type, string $environment, $reference, string $status)
    {
        $this->transactions()->create([
            'title' => $title,
            'amount' => $amount,
            'type' => $type,
            'environment' => $environment,
            'reference' => $reference,
            'status' => $status,
        ]);
    }
}
