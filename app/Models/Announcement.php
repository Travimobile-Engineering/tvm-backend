<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    protected $fillable = [
        'title',
        'description',
        'priority',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class)->withPivot('status');
    }
}
