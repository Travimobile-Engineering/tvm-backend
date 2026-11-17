<?php

namespace App\Models\Npis;

use App\Enum\Npis\ParticipantType;
use App\Enum\Npis\RegistrationSource;
use App\Enum\Npis\RegistrationStatus;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $event_id
 * @property string $verified_contact_id
 * @property string|null $active_contact_key
 * @property string $summit_tag_number
 * @property RegistrationStatus $status
 * @property RegistrationSource $source
 * @property ParticipantType $participant_type
 * @property string|null $protocol_category
 * @property string|null $duplicate_of_registration_id
 * @property string|null $merged_into_registration_id
 * @property list<string>|null $review_flags
 * @property string|null $rejection_reason
 * @property int $version
 * @property Carbon|null $submitted_at
 * @property Carbon|null $approved_at
 * @property Carbon|null $rejected_at
 * @property Carbon|null $cancelled_at
 * @property-read SummitEvent $event
 * @property-read VerifiedContact $contact
 * @property-read ParticipantProfile|null $profile
 * @property-read Itinerary|null $itinerary
 * @property-read AccommodationRequest|null $accommodation
 * @property-read Badge|null $badge
 * @property-read ProgrammeInterest|null $interests
 * @property-read Collection<int, TrainingAssignment> $assignments
 * @property-read Collection<int, CheckIn> $checkIns
 * @property-read Certificate|null $certificate
 */
class Registration extends Model
{
    use SoftDeletes;

    protected $table = 'npis_registrations';

    protected $primaryKey = 'id';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'event_id',
        'verified_contact_id',
        'active_contact_key',
        'summit_tag_number',
        'status',
        'source',
        'participant_type',
        'protocol_category',
        'duplicate_of_registration_id',
        'merged_into_registration_id',
        'review_flags',
        'rejection_reason',
        'version',
        'submitted_at',
        'approved_at',
        'rejected_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => RegistrationStatus::class,
            'source' => RegistrationSource::class,
            'participant_type' => ParticipantType::class,
            'review_flags' => 'array',
            'version' => 'integer',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<SummitEvent, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(SummitEvent::class, 'event_id');
    }

    /**
     * @return BelongsTo<VerifiedContact, $this>
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(VerifiedContact::class, 'verified_contact_id');
    }

    /**
     * @return HasOne<ParticipantProfile, $this>
     */
    public function profile(): HasOne
    {
        return $this->hasOne(ParticipantProfile::class, 'registration_id');
    }

    /**
     * @return HasOne<Itinerary, $this>
     */
    public function itinerary(): HasOne
    {
        return $this->hasOne(Itinerary::class, 'registration_id');
    }

    /**
     * @return HasOne<AccommodationRequest, $this>
     */
    public function accommodation(): HasOne
    {
        return $this->hasOne(AccommodationRequest::class, 'registration_id');
    }

    /**
     * @return HasOne<Badge, $this>
     */
    public function badge(): HasOne
    {
        return $this->hasOne(Badge::class, 'registration_id');
    }

    /**
     * @return HasOne<ProgrammeInterest, $this>
     */
    public function interests(): HasOne
    {
        return $this->hasOne(ProgrammeInterest::class, 'registration_id');
    }

    /**
     * @return HasMany<TrainingAssignment, $this>
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(TrainingAssignment::class, 'registration_id');
    }

    /**
     * @return HasMany<CheckIn, $this>
     */
    public function checkIns(): HasMany
    {
        return $this->hasMany(CheckIn::class, 'registration_id');
    }

    /**
     * @return HasOne<Certificate, $this>
     */
    public function certificate(): HasOne
    {
        return $this->hasOne(Certificate::class, 'registration_id');
    }

    /**
     * @return BelongsTo<Registration, $this>
     */
    public function duplicateOf(): BelongsTo
    {
        return $this->belongsTo(self::class, 'duplicate_of_registration_id');
    }

    /**
     * @return BelongsTo<Registration, $this>
     */
    public function mergedInto(): BelongsTo
    {
        return $this->belongsTo(self::class, 'merged_into_registration_id');
    }

    public function isActive(): bool
    {
        return $this->status !== RegistrationStatus::Cancelled && $this->merged_into_registration_id === null;
    }

    public function isPoliceOfficer(): bool
    {
        return $this->participant_type === ParticipantType::Delegate
            && $this->profile?->sector === 'police';
    }

    /**
     * @return list<string>
     */
    public function reviewFlagValues(): array
    {
        return array_values($this->review_flags ?? []);
    }

    public function hasReviewFlag(string $flag): bool
    {
        return in_array($flag, $this->reviewFlagValues(), true);
    }

    public function addReviewFlag(string $flag): void
    {
        $flags = $this->reviewFlagValues();
        if (! in_array($flag, $flags, true)) {
            $flags[] = $flag;
        }
        $this->review_flags = $flags;
    }

    public function removeReviewFlag(string $flag): void
    {
        $this->review_flags = array_values(array_filter(
            $this->reviewFlagValues(),
            static fn (string $value): bool => $value !== $flag
        ));
    }

    public function activeContactKey(): ?string
    {
        return $this->isActive() ? $this->verified_contact_id : null;
    }
}
