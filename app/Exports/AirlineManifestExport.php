<?php

namespace App\Exports;

use App\Libraries\AirlineManifestCargoSheet;
use App\Libraries\AirlineManifestHeaderSheet;
use App\Libraries\AirlineManifestPassengersSheet;
use App\Models\AirlineManifest;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class AirlineManifestExport implements WithMultipleSheets
{
    public function __construct(private readonly AirlineManifest $manifest) {}

    public function sheets(): array
    {
        $this->manifest->loadMissing(['airline', 'crews', 'passengers', 'cargos']);

        return [
            new AirlineManifestHeaderSheet($this->manifest),
            new AirlineManifestPassengersSheet($this->manifest),
            new AirlineManifestCargoSheet($this->manifest),
        ];
    }
}
