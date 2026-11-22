<?php

namespace App\Http\Requests\Emf;

class IncidentStoreRequest extends EmfRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'type' => ['required', 'string', 'max:60'],
            'severity' => ['required', 'in:critical,high,medium,low'],
            'summary' => ['nullable', 'string', 'max:4000'],
            'manifestId' => ['nullable', 'string', 'exists:emf_manifests,id'],
            'alertId' => ['nullable', 'string', 'exists:emf_alerts,id'],
            'watchlistEntryId' => ['nullable', 'string', 'exists:emf_watchlist_entries,id'],
            'location' => ['required', 'string', 'max:200'],
            'dutyPointId' => ['nullable', 'string', 'exists:emf_duty_points,id'],
            'lgaId' => ['nullable', 'string', 'exists:emf_lgas,id'],
            'assigneeId' => ['nullable', 'string', 'exists:emf_officers,id'],
            'source' => ['sometimes', 'string', 'max:40'],
        ];
    }
}
