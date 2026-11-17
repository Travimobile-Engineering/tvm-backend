<?php

namespace App\Models\Npis;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function faqs(): HasMany
    {
        return $this->hasMany(Faq::class, 'event_id');
    }

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
