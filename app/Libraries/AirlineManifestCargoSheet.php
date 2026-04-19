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

class AirlineManifestCargoSheet implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    public function __construct(private readonly AirlineManifest $manifest) {}

    public function title(): string
    {
        return 'Cargo';
    }

    public function collection(): Collection
    {
        return $this->manifest->cargos;
    }

    public function headings(): array
    {
        return ['#', 'Description', 'Pcs', 'Company', 'Weight (KGS)', 'From', 'To'];
    }

    public function map($cargo): array
    {
        return [
            $cargo->row_number,
            $cargo->description,
            $cargo->pcs,
            $cargo->company,
            $cargo->weight,
            $cargo->from_location,
            $cargo->to_location,
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
