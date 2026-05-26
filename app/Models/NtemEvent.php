<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $full_name
 * @property string $email
 * @property string $phone_number
 * @property string $organization
 * @property string $job_title
 * @property string $state
 * @property string|null $referral_source
 * @property string $dietary_preference
 */
class NtemEvent extends Model
{
    protected $fillable = [
        'full_name',
        'email',
        'phone_number',
        'organization',
        'job_title',
        'state',
        'referral_source',
        'dietary_preference',
    ];
}
