<?php

namespace App\Libraries;

use App\Models\AirlineManifest;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AirlineManifestHeaderSheet implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    public function __construct(private readonly AirlineManifest $manifest) {}

    public function title(): string
    {
        return 'Airline Manifest Header';
    }

    public function collection(): Collection
    {
        $rows = collect([[
            'type' => 'header',
            'manifest_number' => $this->manifest->manifest_number,
            'airline' => $this->manifest->airline->name,
            'routing' => implode('|', $this->manifest->routing ?? []),
            'standby_location' => $this->manifest->standby_location,
            'aircraft_type' => $this->manifest->aircraft_type,
            'aircraft_registration' => $this->manifest->aircraft_registration,
            'customer' => $this->manifest->customer,
            'planned_departure_time' => $this->manifest->planned_departure_time?->format('Y-m-d H:i'),
            'flight_date' => $this->manifest->flight_date?->format('Y-m-d'),
            'status' => $this->manifest->status,
            'total_payload_wt' => $this->manifest->total_payload_wt.' KGS',
            'client_rep_name' => $this->manifest->client_rep_name,
            'time_received' => $this->manifest->time_received,
            'time_closed' => $this->manifest->time_closed,
            'reason_for_delay' => $this->manifest->reason_for_delay,
            'name' => null,
            'role' => null,
        ]]);

        foreach ($this->manifest->crews as $crew) {
            $rows->push([
                'type' => 'crew',
                'manifest_number' => null,
                'airline' => null,
                'routing' => null,
                'standby_location' => null,
                'aircraft_type' => null,
                'aircraft_registration' => null,
                'customer' => null,
                'planned_departure_time' => null,
                'flight_date' => null,
                'status' => null,
                'total_payload_wt' => null,
                'client_rep_name' => null,
                'time_received' => null,
                'time_closed' => null,
                'reason_for_delay' => null,
                'name' => $crew->name,
                'role' => $crew->role,
            ]);
        }

        return $rows;
    }

    public function headings(): array
    {
        return [
            'type', 'manifest_number', 'airline', 'routing', 'standby_location',
            'aircraft_type', 'aircraft_registration', 'customer',
            'planned_departure_time', 'flight_date', 'status', 'total_payload_wt',
            'client_rep_name', 'time_received', 'time_closed', 'reason_for_delay',
            'name', 'role',
        ];
    }

    public function map($row): array
    {
        return array_values($row);
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E3A5F']],
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            ],
        ];
    }
}
