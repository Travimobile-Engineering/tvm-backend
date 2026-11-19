<?php

namespace App\Http\Controllers\Api\V1\ManifestChecker;

use App\Models\Emf\Lga;
use App\Models\Emf\State;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MetaController extends EmfApiController
{
    public function meta(Request $request): JsonResponse
    {
        $officer = $this->officer($request);
        /** @var array<string, string> $modes */
        $modes = (array) config('emf.modes', []);
        /** @var list<string> $verdicts */
        $verdicts = (array) config('emf.verdicts', []);
        /** @var list<string> $alertTypes */
        $alertTypes = (array) config('emf.alert_types', []);
        /** @var list<string> $riskLevels */
        $riskLevels = (array) config('emf.risk_levels', []);
        /** @var list<string> $severities */
        $severities = (array) config('emf.severities', []);
        /** @var list<string> $caseStatuses */
        $caseStatuses = (array) config('emf.case_statuses', []);
        /** @var list<string> $dutyPointTypes */
        $dutyPointTypes = (array) config('emf.duty_point_types', []);

        return $this->success([
            'officer' => [
                'officerId' => $officer->id,
                'fullName' => $officer->full_name,
                'role' => $officer->role,
                'stateCode' => $officer->state_code,
                'permissions' => $officer->permissions(),
            ],
            'modes' => collect($modes)->map(fn (string $label, string $key): array => ['key' => $key, 'label' => $label])->values()->all(),
            'verdicts' => collect($verdicts)->map(fn (string $value): array => ['key' => $value, 'label' => ucfirst($value)])->values()->all(),
            'alertTypes' => collect($alertTypes)->map(fn (string $value): array => ['key' => $value, 'label' => ucfirst($value)])->values()->all(),
            'riskLevels' => collect($riskLevels)->map(fn (string $value): array => ['key' => $value, 'label' => ucfirst($value)])->values()->all(),
            'severities' => collect($severities)->map(fn (string $value): array => ['key' => $value, 'label' => ucfirst($value)])->values()->all(),
            'caseStatuses' => collect($caseStatuses)->map(fn (string $value): array => ['key' => $value, 'label' => str($value)->replace('_', ' ')->title()])->values()->all(),
            'dutyPointTypes' => collect($dutyPointTypes)->map(fn (string $value): array => ['key' => $value, 'label' => str($value)->replace('_', ' ')->title()])->values()->all(),
            'periods' => [
                ['key' => '24h', 'label' => 'Last 24 hours'],
                ['key' => '7d', 'label' => 'Last 7 days'],
                ['key' => '30d', 'label' => 'Last 30 days'],
                ['key' => 'quarter', 'label' => 'Last quarter'],
            ],
            'states' => $this->stateList(true),
        ], 'Manifest Checker reference data.');
    }

    public function states(): JsonResponse
    {
        return $this->success($this->stateList(true), 'States and LGAs.');
    }

    public function systemStates(): JsonResponse
    {
        return $this->success([
            'loading' => ['key' => 'loading', 'title' => 'Loading', 'description' => 'Use when a request is in flight and no cached data exists.'],
            'empty' => ['key' => 'empty', 'title' => 'No records', 'description' => 'Use when the request succeeded with zero results.'],
            'error' => ['key' => 'error', 'title' => 'Something went wrong', 'description' => 'Use with a retry action for 5xx and network failures.'],
            'no_permission' => ['key' => 'no_permission', 'title' => 'No permission', 'description' => 'Use with the required permission and a request-access action.'],
            'session_expired' => ['key' => 'session_expired', 'title' => 'Session expired', 'description' => 'Return to sign-in; the API returns 401 UNAUTHENTICATED.'],
        ], 'System state guidance.');
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function stateList(bool $includeLgas = false): array
    {
        $states = State::query()->with('lgas')->orderBy('sort_order')->get();

        return $states->map(fn (State $state): array => [
            'code' => $state->code,
            'name' => $state->name,
            'lgaCount' => $state->lgas->count(),
            'lgas' => $includeLgas ? $state->lgas->map(fn (Lga $lga): array => ['lgaId' => $lga->id, 'name' => $lga->name])->values()->all() : [],
        ])->values()->all();
    }
}
