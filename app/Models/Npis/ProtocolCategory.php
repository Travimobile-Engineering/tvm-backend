<?php

namespace App\Models\Npis;

use Illuminate\Database\Eloquent\Model;

class ProtocolCategory extends Model
{
    protected $table = 'npis_protocol_categories';

    protected $primaryKey = 'code';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'code',
        'name',
        'members',
        'color',
        'text_color',
        'photo_required',
        'reserved',
        'zone_access',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'photo_required' => 'boolean',
            'reserved' => 'boolean',
            'zone_access' => 'array',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Protocol payload used by the API contract.
     *
     * @return array<string, mixed>
     */
    public function toContract(): array
    {
        return [
            'code' => $this->code,
            'name' => $this->name,
            'members' => $this->members,
            'color' => $this->color,
            'textColor' => $this->text_color,
            'photoRequired' => (bool) $this->photo_required,
            'reserved' => (bool) $this->reserved,
            'zoneAccess' => $this->zone_access ?? [],
        ];
    }
}
