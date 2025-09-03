<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    protected $fillable = [
        'user_id',
        'balance',
        'earnings',
        'is_flagged',
    ];

    protected function casts(): array
    {
        return [
            'balance' => 'decimal:2',
            'earnings' => 'decimal:2',
            'is_flagged' => 'boolean',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
