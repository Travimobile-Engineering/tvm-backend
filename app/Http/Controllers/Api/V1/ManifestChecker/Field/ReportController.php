<?php

namespace App\Http\Controllers\Api\V1\ManifestChecker\Field;

use App\Contracts\NpisCloudStorage;
use App\Http\Controllers\Api\V1\ManifestChecker\EmfApiController;
use App\Http\Requests\Emf\Field\AttachmentRequest;
use App\Http\Requests\Emf\Field\NoteRequest;
use App\Http\Requests\Emf\Field\ReportRequest;
use App\Http\Requests\Emf\Field\SyncReportsRequest;
use App\Models\Emf\Incident;
use App\Models\Emf\IncidentAttachment;
use App\Models\Emf\Officer;
use App\Services\Emf\Field\EmfFieldReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ReportController extends EmfApiController
{
    public function __construct(
        private readonly EmfFieldReportService $reports,
        private readonly NpisCloudStorage $cloud,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'tab' => ['sometimes', 'in:open,closed'],
            'severity' => ['sometimes', 'in:low,medium,high,critical'],
            'search' => ['sometimes', 'string', 'max:120'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'pageSize' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        return $this->success($this->reports->paginate($this->officer($request), $filters), 'My reports.');
    }

    public function meta(): JsonResponse
    {
        return $this->success($this->reports->meta(), 'Report form reference data.');
    }

    public function show(Request $request, Incident $report): JsonResponse
    {
        $this->guardReport($report, $this->officer($request));

        return $this->success($this->reports->show($report), 'Report detail.');
    }

    public function store(ReportRequest $request): JsonResponse
    {
        $payload = $request->validated();
        $evidence = (array) ($payload['evidence'] ?? []);

        foreach ((array) $request->file('evidenceFiles', []) as $file) {
            $upload = $this->cloud->uploadFile($file, 'emf/reports');
            $evidence[] = [
                'type' => 'photo',
                'fileUrl' => $upload['url'],
                'fileSize' => $file->getSize(),
            ];
        }

        $payload['evidence'] = $evidence;
        $report = $this->reports->create($payload, $this->officer($request), $request);

        return $this->success($this->reports->show($report), 'Report filed.', 201);
    }

    public function sync(SyncReportsRequest $request): JsonResponse
    {
        return $this->success($this->reports->sync(
            (array) $request->input('reports', []),
            $this->officer($request),
            $request,
        ), 'Reports synced.');
    }

    public function addNote(NoteRequest $request, Incident $report): JsonResponse
    {
        $officer = $this->officer($request);
        $this->guardReport($report, $officer);

        return $this->success($this->reports->addNote($report, (string) $request->string('note'), $officer, $request), 'Report note added.');
    }

    public function sign(Request $request, Incident $report): JsonResponse
    {
        $officer = $this->officer($request);
        $this->guardReport($report, $officer);

        return $this->success($this->reports->sign($report, $officer, $request), 'Report signed and locked.');
    }

    public function addAttachment(AttachmentRequest $request, Incident $report): JsonResponse
    {
        $officer = $this->officer($request);
        $this->guardReport($report, $officer);

        $payload = $request->validated();
        $fileUrl = $payload['fileUrl'] ?? null;
        $fileSize = $payload['fileSize'] ?? null;

        if ($request->hasFile('file')) {
            $upload = uploadFile($request, 'file', 'emf/reports');
            $fileUrl = $upload['url'];
            $fileSize = $request->file('file')?->getSize();
        }

        $attachment = $this->reports->addAttachment($report, [
            'type' => $payload['type'],
            'fileUrl' => $fileUrl,
            'fileSize' => $fileSize,
            'durationSeconds' => $payload['durationSeconds'] ?? null,
        ], $officer, $request);

        return $this->success([
            'attachmentId' => $attachment->id,
            'type' => $attachment->attachment_type,
            'fileUrl' => $attachment->file_url,
            'report' => $this->reports->show($report->fresh()),
        ], 'Report evidence added.', 201);
    }

    public function downloadAttachment(IncidentAttachment $attachment): Response
    {
        $contents = $this->cloud->fetch($attachment->file_url);

        if ($contents === null) {
            abort(404, 'Evidence file is not available.');
        }

        return response($contents, 200, [
            'Content-Type' => 'application/octet-stream',
            'Content-Disposition' => 'attachment; filename="'.$attachment->id.'"',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    private function guardReport(Incident $report, Officer $officer): void
    {
        if ($report->reported_by !== $officer->id && ! $officer->hasPermission('audit:read')) {
            abort(404, 'Report not found.');
        }
    }
}
