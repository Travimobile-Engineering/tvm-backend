<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AgentClassification extends Model
{
    protected $fillable = ['level', 'amount'];

    public function agents()
    {
        return $this->hasMany(User::class, 'classification_id');
    }
}
