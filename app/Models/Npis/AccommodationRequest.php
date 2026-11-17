<?php

namespace App\Models\Npis;

use App\Casts\EncryptedAttribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $registration_id
 * @property string $preference
 * @property Carbon|null $check_in_date
 * @property Carbon|null $check_out_date
 * @property string|null $special_needs
 * @property bool $allowance_requested
 * @property string|null $payout_bank_code
 * @property string|null $payout_account_number
 * @property string|null $payout_account_name
 * @property string $allocation_status
 * @property-read Registration $registration
 * @property-read AccommodationAllocation|null $allocation
 */
class AccommodationRequest extends Model
{
    protected $table = 'npis_accommodation_requests';

    protected $fillable = [
        'registration_id',
        'preference',
        'check_in_date',
        'check_out_date',
        'special_needs',
        'allowance_requested',
        'payout_bank_code',
        'payout_account_number',
        'payout_account_name',
        'allocation_status',
    ];

    protected $hidden = ['payout_account_number', 'payout_account_name'];

    protected function casts(): array
    {
        return [
            'check_in_date' => 'date:Y-m-d',
            'check_out_date' => 'date:Y-m-d',
            'allowance_requested' => 'boolean',
            'payout_account_number' => EncryptedAttribute::class,
            'payout_account_name' => EncryptedAttribute::class,
        ];
    }

    /**
     * @return BelongsTo<Registration, $this>
     */
    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class, 'registration_id');
    }

    /**
     * @return HasOne<AccommodationAllocation, $this>
     */
    public function allocation(): HasOne
    {
        return $this->hasOne(AccommodationAllocation::class, 'accommodation_request_id');
    }
}
