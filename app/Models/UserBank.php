<?php

namespace App\Models;

use App\Casts\EncryptedAttribute;
use App\Services\DataProtection\DataProtector;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class UserBank extends Model
{
    protected $fillable = [
        'user_id',
        'bank_name',
        'account_number',
        'account_name',
        'recipient_code',
        'data',
        'is_default',
    ];

    protected $hidden = [
        'account_number_hash',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'is_default' => 'boolean',
            'account_number' => EncryptedAttribute::class,
            'account_name' => EncryptedAttribute::class,
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $bank): void {
            $attributes = $bank->getAttributes();

            if (array_key_exists('account_number', $attributes)) {
                $number = $bank->getAttribute('account_number');
                $bank->account_number_hash = filled($number)
                    ? app(DataProtector::class)->blindIndex((string) $number)
                    : null;
            }
        });
    }

    /**
     * Find bank accounts by the deterministic blind index of the account number.
     *
     * @return Builder<UserBank>
     */
    public static function byAccountNumber(string $accountNumber): Builder
    {
        $hash = app(DataProtector::class)->blindIndex($accountNumber);

        return static::query()->where(function (Builder $query) use ($hash, $accountNumber): void {
            $query->where('account_number_hash', $hash)->orWhere('account_number', $accountNumber);
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
