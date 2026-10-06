<?php

namespace App\Models;

use App\Casts\EncryptedAttribute;
use App\Services\DataProtection\DataProtector;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    protected $table = 'documents';

    protected $fillable = [
        'user_id',
        'type',
        'image_url',
        'public_id',
        'number',
        'expiration_date',
        'status',
    ];

    protected $hidden = [
        'number_hash',
    ];

    protected function casts(): array
    {
        return [
            'number' => EncryptedAttribute::class,
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $document): void {
            $attributes = $document->getAttributes();

            if (array_key_exists('number', $attributes)) {
                $number = $document->getAttribute('number');
                $document->number_hash = filled($number)
                    ? app(DataProtector::class)->blindIndex((string) $number)
                    : null;
            }
        });
    }

    /**
     * Find documents by the deterministic blind index of their number.
     *
     * @return Builder<Document>
     */
    public static function byNumber(string $number): Builder
    {
        $hash = app(DataProtector::class)->blindIndex($number);

        return static::query()->where(function (Builder $query) use ($hash, $number): void {
            $query->where('number_hash', $hash)->orWhere('number', $number);
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
