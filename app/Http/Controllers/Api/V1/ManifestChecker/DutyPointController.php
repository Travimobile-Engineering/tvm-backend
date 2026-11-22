<?php

namespace App\Http\Controllers\Api\V1\ManifestChecker;

use App\Http\Requests\Emf\RosterAssignRequest;
use App\Http\Requests\Emf\RosterWeekRequest;
use App\Models\Emf\DutyPoint;
use App\Services\Emf\EmfAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DutyPointController extends EmfApiController
{
    public function __construct(private readonly EmfAccessService $access) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'stateCode' => ['sometimes', 'string', 'max:8'],
            'type' => ['sometimes', 'in:motor_park,jetty,airport,rail,hotel,other'],
        ]);

        return $this->success($this->access->dutyPoints($filters), 'Duty points.');
    }

    public function show(DutyPoint $dutyPoint): JsonResponse
    {
        $dutyPoint->load(['lga', 'state', 'roster' => fn ($query) => $query->with('officer')->orderBy('roster_date')->limit(14)]);

        return $this->success([
            'dutyPointId' => $dutyPoint->id,
            'name' => $dutyPoint->name,
            'type' => $dutyPoint->type,
            'stateCode' => $dutyPoint->state_code,
            'stateName' => $dutyPoint->state?->name,
            'lgaName' => $dutyPoint->lga?->name,
            'address' => $dutyPoint->address,
            'latitude' => (float) $dutyPoint->latitude,
            'longitude' => (float) $dutyPoint->longitude,
            'officers' => $dutyPoint->officers_count,
            'grants' => $dutyPoint->grants_count,
            'shift' => $dutyPoint->shift_start.'–'.$dutyPoint->shift_end,
            'status' => $dutyPoint->status,
            'roster' => $dutyPoint->roster->map(fn ($slot): array => [
                'rosterId' => $slot->id,
                'date' => $slot->roster_date->format('Y-m-d'),
                'shift' => $slot->shift,
                'officer' => $slot->officer?->full_name,
                'status' => $slot->status,
                'published' => $slot->published,
            ])->values()->all(),
        ], 'Duty point detail.');
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'stateCode' => ['sometimes', 'string', 'exists:emf_states,code'],
            'lgaId' => ['sometimes', 'nullable', 'string', 'exists:emf_lgas,id'],
            'name' => ['required', 'string', 'max:160'],
            'type' => ['required', 'in:motor_park,jetty,airport,rail,hotel,other'],
            'address' => ['sometimes', 'nullable', 'string', 'max:255'],
            'latitude' => ['sometimes', 'nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['sometimes', 'nullable', 'numeric', 'between:-180,180'],
            'shiftStart' => ['sometimes', 'date_format:H:i'],
            'shiftEnd' => ['sometimes', 'date_format:H:i'],
        ]);

        $point = $this->access->createDutyPoint($validated, $this->officer($request), $request);

        return $this->success(['dutyPointId' => $point->id, 'name' => $point->name], 'Duty point created.', 201);
    }

    public function update(Request $request, DutyPoint $dutyPoint): JsonResponse
    {
        $validated = $request->validate([
            'lgaId' => ['sometimes', 'nullable', 'string', 'exists:emf_lgas,id'],
            'name' => ['sometimes', 'string', 'max:160'],
            'type' => ['sometimes', 'in:motor_park,jetty,airport,rail,hotel,other'],
            'address' => ['sometimes', 'nullable', 'string', 'max:255'],
            'latitude' => ['sometimes', 'nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['sometimes', 'nullable', 'numeric', 'between:-180,180'],
            'shiftStart' => ['sometimes', 'date_format:H:i'],
            'shiftEnd' => ['sometimes', 'date_format:H:i'],
            'status' => ['sometimes', 'in:active,inactive'],
        ]);

        $point = $this->access->updateDutyPoint($dutyPoint, $validated, $this->officer($request), $request);

        return $this->success(['dutyPointId' => $point->id, 'name' => $point->name, 'status' => $point->status], 'Duty point updated.');
    }

    public function roster(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'weekStart' => ['sometimes', 'date'],
            'stateCode' => ['sometimes', 'string', 'max:8'],
            'type' => ['sometimes', 'in:motor_park,jetty,airport,rail,hotel,other'],
        ]);

        return $this->success($this->access->roster($filters), 'Duty roster.');
    }

    public function assign(RosterAssignRequest $request): JsonResponse
    {
        $roster = $this->access->assignRoster($request->validated(), $this->officer($request), $request);

        return $this->success([
            'rosterId' => $roster->id,
            'date' => $roster->roster_date->format('Y-m-d'),
            'shift' => $roster->shift,
            'officer' => $roster->officer?->full_name,
            'status' => $roster->status,
        ], 'Roster slot assigned.');
    }

    public function publish(RosterWeekRequest $request): JsonResponse
    {
        $count = $this->access->publishRoster((string) $request->string('weekStart'), $this->officer($request), $request);

        return $this->success(['publishedSlots' => $count], 'Roster published.');
    }

    public function autoFill(RosterWeekRequest $request): JsonResponse
    {
        return $this->success(
            $this->access->autoFillRoster((string) $request->string('weekStart'), $this->officer($request), $request),
            'Roster vacancies filled.'
        );
    }
}
