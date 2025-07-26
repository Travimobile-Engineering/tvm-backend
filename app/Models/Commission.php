<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Commission extends Model
{
    protected $fillable = [
        'agent_id',
        'passenger_id',
        'amount',
        'is_first_time',
        'first_agent_id',
    ];

    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function passenger()
    {
        return $this->belongsTo(User::class, 'passenger_id');
    }

    public function firstAgent()
    {
        return $this->belongsTo(User::class, 'first_agent_id');
    }
}
