<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobApplication extends Model
{
    protected $fillable = [
        'job_opening_id',
        'full_name',
        'dob',
        'gender',
        'state_of_origin',
        'address',
        'phone',
        'email',
        'state_applying_for',
        'highest_level_of_education',
        'field_of_study',
        'resume_url',
        'cover_letter_url',
    ];
}
