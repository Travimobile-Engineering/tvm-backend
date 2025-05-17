<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WatchList extends Model
{
    protected $fillable = [
        "full_name",
        "category",
        "phone",
        "email",
        "dob",
        "state_of_origin",
        "nin",
        "investigation_officer",
        "io_contact_number",
        "alert_location",
        "reason",
        "recent_location",
        "photo_url",
        "documents",
        "observation",
    ];
}
