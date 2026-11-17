<?php

namespace App\Models\Npis;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $name
 * @property Carbon $start_date
 * @property Carbon $end_date
 * @property string $timezone
 * @property string $venue_name
 * @property string $venue_address
 * @property string|null $theme
 * @property string|null $welcome_message_title
 * @property string|null $welcome_message_author
 * @property string|null $welcome_message_author_title
 * @property list<string>|null $welcome_message_paragraphs
 * @property Carbon|null $welcome_message_approved_at
 * @property string|null $brochure_title
 * @property string|null $brochure_file_name
 * @property string|null $brochure_path
 * @property string|null $brochure_url
 * @property string|null $brochure_public_id
 * @property int|null $brochure_size_bytes
 * @property string|null $brochure_version
 * @property Carbon|null $brochure_published_at
 * @property array<string, mixed> $deadlines
 * @property array<string, mixed> $policies
 * @property-read Collection<int, Faq> $faqs
 * @property-read Collection<int, TrainingSession> $trainingSessions
 */
class SummitEvent extends Model
{
    protected $table = 'npis_summit_events';

    protected $primaryKey = 'id';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'name',
        'start_date',
        'end_date',
        'timezone',
        'venue_name',
        'venue_address',
        'theme',
        'welcome_message_title',
        'welcome_message_author',
        'welcome_message_author_title',
        'welcome_message_paragraphs',
        'welcome_message_approved_at',
        'brochure_title',
        'brochure_file_name',
        'brochure_path',
        'brochure_url',
        'brochure_public_id',
        'brochure_size_bytes',
        'brochure_version',
        'brochure_published_at',
        'deadlines',
        'policies',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date:Y-m-d',
            'end_date' => 'date:Y-m-d',
            'welcome_message_paragraphs' => 'array',
            'welcome_message_approved_at' => 'datetime',
            'brochure_published_at' => 'datetime',
            'brochure_size_bytes' => 'integer',
            'deadlines' => 'array',
            'policies' => 'array',
        ];
    }

    /**
     * @return HasMany<Faq, $this>
     */
    public function faqs(): HasMany
    {
        return $this->hasMany(Faq::class, 'event_id');
    }

    /**
     * @return HasMany<TrainingSession, $this>
     */
    public function trainingSessions(): HasMany
    {
        return $this->hasMany(TrainingSession::class, 'event_id');
    }

    public function deadline(string $key): ?string
    {
        return $this->deadlines[$key] ?? null;
    }

    public function policy(string $key, mixed $default = null): mixed
    {
        return $this->policies[$key] ?? $default;
    }

    public function verificationPolicy(string $key, mixed $default = null): mixed
    {
        return $this->policies['verification'][$key] ?? $default;
    }

    public function isWithinEventDates(string $date): bool
    {
        return $date >= $this->start_date->format('Y-m-d')
            && $date <= $this->end_date->format('Y-m-d');
    }
}
