<?php

namespace App\Models;

use App\Casts\EncryptedJson;
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
            'response' => EncryptedJson::class,
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
