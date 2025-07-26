<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AgentCommission extends Model
{
    protected $fillable = ['type', 'amount'];

    public const PRIMARY = 'Primary';
    public const SECONDARY = 'Secondary';
}
