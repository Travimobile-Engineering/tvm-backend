<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountTransfer extends Model
{
    protected $fillable = [
        'account_id',
        'amount',
        'reference',
        'transfer_code',
        'response',
        'status',
        'admin_bulk_transfer_id',
    ];

    protected function casts(): array
    {
        return [
            'response' => 'array'
        ];
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function adminBulkTransfer()
    {
        return $this->belongsTo(AdminBulkTransfer::class);
    }
}
