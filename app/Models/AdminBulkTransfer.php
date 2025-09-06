<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminBulkTransfer extends Model
{
    protected $fillable = [
        'reference',
        'transfer_code',
        'total_amount',
        'total_transfers',
        'response',
        'status',
        'processed_at',
    ];

    protected $casts = [
        'response' => 'array',
        'processed_at' => 'datetime',
    ];

    public function accountTransfers()
    {
        return $this->hasMany(AccountTransfer::class);
    }
}
