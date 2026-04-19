<?php

namespace App\Imports;

use App\Models\AirlineManifest;
use App\Models\AirlineManifestCargo;
use App\Models\AirlineManifestCrew;
use App\Models\AirlineManifestPassenger;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class AirlineManifestImport implements ToCollection
{
    public ?AirlineManifest $manifest = null;

    public array $errors = [];

    public function __construct(private int $airlineId) {}

    public function collection(Collection $rows): void
    {
        $headerData = [];
        $passengers = [];
        $cargos = [];
        $crews = [];

        foreach ($rows as $index => $row) {
            $row = $row->map(fn ($v) => is_string($v) ? trim($v) : $v);
            $type = strtolower($row->get('type', ''));

            match ($type) {
                'header' => $headerData = $this->extractHeader($row),
                'passenger' => $passengers[] = $this->extractPassenger($row, $index + 2),
                'cargo' => $cargos[] = $this->extractCargo($row, $index + 2),
                'crew' => $crews[] = $this->extractCrew($row),
                default => null,
            };
        }

        if (empty($headerData)) {
            $this->errors[] = 'No header row (type=header) found in the file.';

            return;
        }

        // Build routing array from pipe-separated string e.g. "NAF|TORU-N|NAF"
        $routing = isset($headerData['routing'])
            ? array_map('trim', explode('|', $headerData['routing']))
            : [];

        $this->manifest = AirlineManifest::create([
            'airline_id' => $this->airlineId,
            'routing' => $routing,
            'standby_location' => $headerData['standby_location'] ?? null,
            'aircraft_type' => $headerData['aircraft_type'] ?? '',
            'aircraft_registration' => $headerData['aircraft_registration'] ?? '',
            'customer' => $headerData['customer'] ?? '',
            'planned_departure_time' => $headerData['planned_departure_time'] ?? null,
            'flight_date' => $headerData['flight_date'] ?? now()->toDateString(),
            'client_rep_name' => $headerData['client_rep_name'] ?? null,
            'time_received' => $headerData['time_received'] ?? null,
            'time_closed' => $headerData['time_closed'] ?? null,
            'reason_for_delay' => $headerData['reason_for_delay'] ?? null,
            'source' => 'file_upload',
        ]);

        foreach ($crews as $crew) {
            AirlineManifestCrew::create(['airline_manifest_id' => $this->manifest->id] + $crew);
        }

        foreach ($passengers as $pax) {
            AirlineManifestPassenger::create(['airline_manifest_id' => $this->manifest->id] + $pax);
        }

        foreach ($cargos as $cargo) {
            AirlineManifestCargo::create(['airline_manifest_id' => $this->manifest->id] + $cargo);
        }

        $this->manifest->recalculateTotals();
    }

    private function extractHeader(Collection $row): array
    {
        return [
            'routing' => $row->get('routing'),
            'standby_location' => $row->get('standby_location'),
            'aircraft_type' => $row->get('aircraft_type'),
            'aircraft_registration' => $row->get('aircraft_registration'),
            'customer' => $row->get('customer'),
            'planned_departure_time' => $row->get('planned_departure_time'),
            'flight_date' => $row->get('flight_date'),
            'client_rep_name' => $row->get('client_rep_name'),
            'time_received' => $row->get('time_received'),
            'time_closed' => $row->get('time_closed'),
            'reason_for_delay' => $row->get('reason_for_delay'),
        ];
    }

    private function extractPassenger(Collection $row, int $rowNum): array
    {
        return [
            'row_number' => (int) ($row->get('row_number') ?? $rowNum),
            'name' => $row->get('name', ''),
            'job' => $row->get('job'),
            'company' => $row->get('company'),
            'bag_pcs' => (int) ($row->get('bag_pcs') ?? 0),
            'bag_wt' => (float) ($row->get('bag_wt') ?? 0),
            'pax_wt' => (float) ($row->get('pax_wt') ?? 0),
            'from_location' => $row->get('from_location'),
            'to_location' => $row->get('to_location'),
            'is_special_cargo' => filter_var($row->get('is_special_cargo', false), FILTER_VALIDATE_BOOLEAN),
            'special_cargo_type' => $row->get('special_cargo_type'),
        ];
    }

    private function extractCargo(Collection $row, int $rowNum): array
    {
        return [
            'row_number' => (int) ($row->get('row_number') ?? $rowNum),
            'description' => $row->get('description', ''),
            'pcs' => (int) ($row->get('pcs') ?? 0),
            'company' => $row->get('company'),
            'weight' => (float) ($row->get('weight') ?? 0),
            'from_location' => $row->get('from_location'),
            'to_location' => $row->get('to_location'),
        ];
    }

    private function extractCrew(Collection $row): array
    {
        return [
            'name' => $row->get('name', ''),
            'role' => $row->get('role'),
        ];
    }
}
