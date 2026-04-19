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

class AirlineManifestPassengersSheet implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    public function __construct(private readonly AirlineManifest $manifest) {}

    public function title(): string
    {
        return 'Passengers';
    }

    public function collection(): Collection
    {
        return $this->manifest->passengers;
    }

    public function headings(): array
    {
        return [
            '#', 'Name', 'Job', 'Company',
            'Bag Pcs', 'Bag Wt (KGS)', 'Pax Wt (KGS)', 'Total Wt (KGS)',
            'From', 'To', 'Special Cargo', 'Cargo Type',
        ];
    }

    public function map($pax): array
    {
        return [
            $pax->row_number,
            $pax->name,
            $pax->job,
            $pax->company,
            $pax->bag_pcs,
            $pax->bag_wt,
            $pax->pax_wt,
            $pax->total_wt,
            $pax->from_location,
            $pax->to_location,
            $pax->is_special_cargo ? 'YES' : 'NO',
            $pax->special_cargo_type,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E3A5F']],
            ],
        ];
    }
}
