<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    protected $fillable = [
        'user_id',
        'balance',
        'earnings',
    ];

    public function casts(): array
    {
        return [
            'balance' => 'decimal:10,2',
            'earnings' => 'decimal:10,2',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
