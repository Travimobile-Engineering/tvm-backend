<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class AirlineManifest extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'airline_id',
        'manifest_number',
        'routing',
        'standby_location',
        'aircraft_type',
        'aircraft_registration',
        'customer',
        'planned_departure_time',
        'flight_date',
        'total_bag_pcs',
        'total_bag_wt',
        'total_pax_count',
        'total_pax_wt',
        'total_cargo_pcs',
        'total_cargo_wt',
        'total_payload_wt',
        'client_rep_name',
        'time_received',
        'time_closed',
        'reason_for_delay',
        'status',
        'source',
    ];

    protected function casts(): array
    {
        return [
            'routing' => 'array',
            'planned_departure_time' => 'datetime',
            'flight_date' => 'date',
            'total_bag_wt' => 'decimal:2',
            'total_pax_wt' => 'decimal:2',
            'total_cargo_wt' => 'decimal:2',
            'total_payload_wt' => 'decimal:2',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $manifest) {
            if (empty($manifest->manifest_number)) {
                $manifest->manifest_number = self::generateManifestNumber();
            }
        });
    }

    public static function generateManifestNumber(): string
    {
        do {
            $number = 'MNF-'.now()->format('Ymd').'-'.strtoupper(Str::random(5));
        } while (self::where('manifest_number', $number)->exists());

        return $number;
    }

    public function airline(): BelongsTo
    {
        return $this->belongsTo(Airline::class);
    }

    public function crews(): HasMany
    {
        return $this->hasMany(AirlineManifestCrew::class);
    }

    public function passengers(): HasMany
    {
        return $this->hasMany(AirlineManifestPassenger::class)->orderBy('row_number');
    }

    public function cargos(): HasMany
    {
        return $this->hasMany(AirlineManifestCargo::class)->orderBy('row_number');
    }

    public function recalculateTotals(): void
    {
        $passengers = $this->passengers;
        $this->total_bag_pcs = $passengers->sum('bag_pcs');
        $this->total_bag_wt = $passengers->sum('bag_wt');
        $this->total_pax_count = $passengers->where('is_special_cargo', false)->count();
        $this->total_pax_wt = $passengers->where('is_special_cargo', false)->sum('pax_wt');

        $cargos = $this->cargos;
        $this->total_cargo_pcs = $cargos->sum('pcs');
        $this->total_cargo_wt = $cargos->sum('weight');

        $this->total_payload_wt = $passengers->sum('total_wt') + $this->total_cargo_wt;

        $this->saveQuietly();
    }
}
