<?php

namespace App\Models\Npis;

use Illuminate\Database\Eloquent\Model;

class OrganizerAccess extends Model
{
    protected $table = 'npis_organizer_access';

    protected $fillable = ['code_hash', 'updated_by'];

    public static function current(): self
    {
        return self::query()->firstOrCreate([], [
            'code_hash' => bcrypt(config('npis.organizer.default_access_code')),
        ]);
    }
}
