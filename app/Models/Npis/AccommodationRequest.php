<?php

namespace App\Models\Npis;

use App\Casts\EncryptedAttribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

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

    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class, 'registration_id');
    }

    public function allocation(): HasOne
    {
        return $this->hasOne(AccommodationAllocation::class, 'accommodation_request_id');
    }
}
