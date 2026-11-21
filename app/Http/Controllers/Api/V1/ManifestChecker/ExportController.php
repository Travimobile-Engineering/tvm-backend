<?php

namespace App\Http\Controllers\Api\V1\ManifestChecker;

use App\Contracts\NpisCloudStorage;
use App\Models\Emf\CaseEvidence;
use App\Models\Emf\ExportJob;
use App\Services\Emf\EmfExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class ExportController extends EmfApiController
{
    public function __construct(
        private readonly EmfExportService $exports,
        private readonly NpisCloudStorage $cloud,
    ) {}

    public function show(ExportJob $export): Response|JsonResponse
    {
        return $this->success($this->exports->contract($export), 'Export job.');
    }

    public function download(ExportJob $export): Response
    {
        $contents = $export->file_url ? $this->cloud->fetch($export->file_url) : null;

        if ($contents === null) {
            abort(404, 'Export file is not available.');
        }

        return response($contents, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$export->id.'.csv"',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function evidence(CaseEvidence $evidence): Response
    {
        $contents = $this->cloud->fetch($evidence->file_url);

        if ($contents === null) {
            abort(404, 'Evidence file is not available.');
        }

        return response($contents, 200, [
            'Content-Type' => 'application/octet-stream',
            'Content-Disposition' => 'attachment; filename="'.$evidence->id.'"',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
