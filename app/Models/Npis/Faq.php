<?php

namespace App\Models\Npis;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Faq extends Model
{
    use SoftDeletes;

    protected $table = 'npis_faqs';

    protected $primaryKey = 'id';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'event_id',
        'question',
        'answer',
        'category',
        'sort_order',
        'published',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'published' => 'boolean',
        ];
    }
}
