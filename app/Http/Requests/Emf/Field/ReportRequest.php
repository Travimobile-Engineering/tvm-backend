<?php

namespace App\Http\Requests\Emf\Field;

use App\Http\Requests\Emf\EmfRequest;

class ReportRequest extends EmfRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'category' => ['required', 'in:unregistered_manifest,suspicious_person,document_nin_fraud,watchlist_hit,overloading_unmanifested,vehicle_evaded_stop,security_threat_distress'],
            'severity' => ['required', 'in:low,medium,high,critical'],
            'title' => ['nullable', 'string', 'max:200'],
            'note' => ['nullable', 'string', 'max:4000'],
            'manifestId' => ['nullable', 'string', 'exists:emf_manifests,id'],
            'location' => ['nullable', 'string', 'max:200'],
            'linkedVehicle' => ['nullable', 'string', 'max:32'],
            'seat' => ['nullable', 'string', 'max:16'],
            'seatId' => ['nullable', 'string', 'max:64'],
            'fieldCheckId' => ['nullable', 'string', 'exists:emf_field_checks,id'],
            'gpsLatitude' => ['nullable', 'numeric', 'between:-90,90'],
            'gpsLongitude' => ['nullable', 'numeric', 'between:-180,180'],
            'occurredAt' => ['nullable', 'date'],
            'saveOffline' => ['sometimes', 'boolean'],
            'escalateToOps' => ['sometimes', 'boolean'],
            'clientReportId' => ['nullable', 'string', 'max:80'],
            'localId' => ['nullable', 'string', 'max:80'],
            'assigneeId' => ['nullable', 'string', 'exists:emf_officers,id'],
            'evidence' => ['sometimes', 'array'],
            'evidence.*.type' => ['required_with:evidence', 'in:photo,voice_note,document'],
            'evidence.*.fileUrl' => ['required_with:evidence', 'url', 'max:2048'],
            'evidence.*.fileSize' => ['nullable', 'integer', 'min:1'],
            'evidence.*.durationSeconds' => ['nullable', 'integer', 'min:1'],
            'evidenceFiles' => ['sometimes', 'array'],
            'evidenceFiles.*' => ['file', 'max:10240'],
        ];
    }
}
