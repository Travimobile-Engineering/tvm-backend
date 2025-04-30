<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WatchList extends Model
{
    protected $fillable = [
        "full_name",
        "phone",
        "email",
        "dob",
        "state_of_origin",
        "nin",
        "investigation_officer",
        "io_contact_number",
        "alert_location",
        "photo_url",
        "documents",
    ];
}
