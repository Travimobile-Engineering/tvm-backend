<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AgentClassification extends Model
{
    protected $fillable = ['level', 'amount', 'reward_amount'];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'reward_amount' => 'decimal:2',
        ];
    }

    public function agents()
    {
        return $this->hasMany(User::class, 'classification_id');
    }
}
