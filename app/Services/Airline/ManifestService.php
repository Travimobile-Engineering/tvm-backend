<?php

namespace App\Services\Airline;

use App\Models\Airline;
use App\Models\AirlineManifest;
use App\Models\AirlineManifestCargo;
use App\Models\AirlineManifestCrew;
use App\Models\AirlineManifestPassenger;
use Illuminate\Support\Facades\DB;

class ManifestService
{
    /**
     * Create a manifest from validated request data.
     *
     * @param  array  $data  Output of CreateManifestRequest::validated()
     * @param  string  $source  'manual' | 'file_upload'
     */
    public function create(Airline $airline, array $data, string $source = 'manual'): AirlineManifest
    {
        return DB::transaction(function () use ($data, $source, $airline) {

            $manifest = AirlineManifest::create([
                'airline_id' => $data['airline_id'],
                'routing' => $data['routing'],
                'standby_location' => $data['standby_location'] ?? null,
                'aircraft_type' => $data['aircraft_type'],
                'aircraft_registration' => $data['aircraft_registration'],
                'customer' => $data['customer'],
                'planned_departure_time' => $data['planned_departure_time'] ?? null,
                'flight_date' => $data['flight_date'],
                'client_rep_name' => $data['client_rep_name'] ?? null,
                'time_received' => $data['time_received'] ?? null,
                'time_closed' => $data['time_closed'] ?? null,
                'reason_for_delay' => $data['reason_for_delay'] ?? null,
                'source' => $source,
            ]);

            $this->syncCrews($manifest, $data['crews'] ?? []);
            $this->syncPassengers($manifest, $data['passengers'] ?? []);
            $this->syncCargos($manifest, $data['cargos'] ?? []);

            $manifest->recalculateTotals();

            $airline->wallet()->decrement('balance', 1000);

            return $manifest->load(['airline', 'crews', 'passengers', 'cargos']);
        });
    }

    /**
     * Replace all crews on a manifest.
     */
    public function syncCrews(AirlineManifest $manifest, array $crews): void
    {
        $manifest->crews()->delete();

        foreach ($crews as $crew) {
            AirlineManifestCrew::create([
                'airline_manifest_id' => $manifest->id,
                'name' => $crew['name'],
                'role' => $crew['role'] ?? null,
            ]);
        }
    }

    /**
     * Replace all passengers on a manifest.
     */
    public function syncPassengers(AirlineManifest $manifest, array $passengers): void
    {
        $manifest->passengers()->delete();

        foreach ($passengers as $pax) {
            AirlineManifestPassenger::create([
                'airline_manifest_id' => $manifest->id,
                'row_number' => $pax['row_number'],
                'name' => $pax['name'],
                'job' => $pax['job'] ?? null,
                'company' => $pax['company'] ?? null,
                'bag_pcs' => $pax['bag_pcs'] ?? 0,
                'bag_wt' => $pax['bag_wt'] ?? 0,
                'pax_wt' => $pax['pax_wt'] ?? 0,
                'from_location' => $pax['from_location'] ?? null,
                'to_location' => $pax['to_location'] ?? null,
                'is_special_cargo' => $pax['is_special_cargo'] ?? false,
                'special_cargo_type' => $pax['special_cargo_type'] ?? null,
            ]);
        }
    }

    /**
     * Replace all cargo rows on a manifest.
     */
    public function syncCargos(AirlineManifest $manifest, array $cargos): void
    {
        $manifest->cargos()->delete();

        foreach ($cargos as $cargo) {
            AirlineManifestCargo::create([
                'airline_manifest_id' => $manifest->id,
                'row_number' => $cargo['row_number'],
                'description' => $cargo['description'],
                'pcs' => $cargo['pcs'] ?? 0,
                'company' => $cargo['company'] ?? null,
                'weight' => $cargo['weight'] ?? 0,
                'from_location' => $cargo['from_location'] ?? null,
                'to_location' => $cargo['to_location'] ?? null,
            ]);
        }
    }

    /**
     * Update manifest header + related rows, then recalculate totals.
     */
    public function update(AirlineManifest $manifest, array $data): AirlineManifest
    {
        return DB::transaction(function () use ($manifest, $data) {

            $manifest->update(array_filter([
                'routing' => $data['routing'] ?? $manifest->routing,
                'standby_location' => $data['standby_location'] ?? $manifest->standby_location,
                'aircraft_type' => $data['aircraft_type'] ?? $manifest->aircraft_type,
                'aircraft_registration' => $data['aircraft_registration'] ?? $manifest->aircraft_registration,
                'customer' => $data['customer'] ?? $manifest->customer,
                'planned_departure_time' => $data['planned_departure_time'] ?? $manifest->planned_departure_time,
                'flight_date' => $data['flight_date'] ?? $manifest->flight_date,
                'client_rep_name' => $data['client_rep_name'] ?? $manifest->client_rep_name,
                'time_received' => $data['time_received'] ?? $manifest->time_received,
                'time_closed' => $data['time_closed'] ?? $manifest->time_closed,
                'reason_for_delay' => $data['reason_for_delay'] ?? $manifest->reason_for_delay,
                'status' => $data['status'] ?? $manifest->status,
            ], fn ($v) => $v !== null));

            if (isset($data['crews'])) {
                $this->syncCrews($manifest, $data['crews']);
            }

            if (isset($data['passengers'])) {
                $this->syncPassengers($manifest, $data['passengers']);
            }

            if (isset($data['cargos'])) {
                $this->syncCargos($manifest, $data['cargos']);
            }

            $manifest->recalculateTotals();

            return $manifest->fresh(['airline', 'crews', 'passengers', 'cargos']);
        });
    }
}
