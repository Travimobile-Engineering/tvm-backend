<?php

namespace App\Http\Controllers\Api\V1\ManifestChecker\Field;

use App\Http\Controllers\Api\V1\ManifestChecker\EmfApiController;
use App\Http\Requests\Emf\Field\ConfirmSeatRequest;
use App\Http\Requests\Emf\Field\EscalateSeatRequest;
use App\Http\Requests\Emf\Field\FlagSeatRequest;
use App\Http\Requests\Emf\Field\HoldRequest;
use App\Http\Requests\Emf\Field\NoteRequest;
use App\Http\Requests\Emf\Field\ReleaseRequest;
use App\Http\Requests\Emf\Field\ResolveSeatRequest;
use App\Http\Requests\Emf\Field\RevealRequest;
use App\Models\Emf\FieldCheck;
use App\Models\Emf\Manifest;
use App\Models\Emf\ManifestPassenger;
use App\Models\Emf\Officer;
use App\Services\Emf\Field\EmfFieldCheckService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CheckController extends EmfApiController
{
    public function __construct(private readonly EmfFieldCheckService $checks) {}

    public function start(Request $request, Manifest $manifest): JsonResponse
    {
        $officer = $this->officer($request);
        $this->guardState($manifest->state_code, $officer);
        $check = $this->checks->start($manifest, $officer, $request);

        return $this->success($this->checks->show($check), 'Manifest check started.', 201);
    }

    public function show(Request $request, FieldCheck $check): JsonResponse
    {
        $this->guardCheck($check, $this->officer($request));

        return $this->success($this->checks->show($check), 'Manifest check workspace.');
    }

    public function record(Request $request, FieldCheck $check): JsonResponse
    {
        $this->guardCheck($check, $this->officer($request));

        return $this->success($this->checks->record($check), 'Manifest check record.');
    }

    public function confirm(ConfirmSeatRequest $request, FieldCheck $check, ManifestPassenger $passenger): JsonResponse
    {
        $officer = $this->officer($request);
        $this->guardCheck($check, $officer);

        return $this->success($this->checks->confirm($check, $passenger, $request->validated(), $officer, $request), 'Seat confirmed.');
    }

    public function flag(FlagSeatRequest $request, FieldCheck $check, ManifestPassenger $passenger): JsonResponse
    {
        $officer = $this->officer($request);
        $this->guardCheck($check, $officer);

        return $this->success($this->checks->flag($check, $passenger, $request->validated(), $officer, $request), 'Seat flagged.');
    }

    public function resolve(ResolveSeatRequest $request, FieldCheck $check, ManifestPassenger $passenger): JsonResponse
    {
        $officer = $this->officer($request);
        $this->guardCheck($check, $officer);

        return $this->success($this->checks->resolve($check, $passenger, $request->validated(), $officer, $request), 'Seat resolved.');
    }

    public function escalate(EscalateSeatRequest $request, FieldCheck $check, ManifestPassenger $passenger): JsonResponse
    {
        $officer = $this->officer($request);
        $this->guardCheck($check, $officer);

        return $this->success($this->checks->escalate($check, $passenger, $request->validated(), $officer, $request), 'Priority alert sent.');
    }

    public function hold(HoldRequest $request, FieldCheck $check): JsonResponse
    {
        $officer = $this->officer($request);
        $this->guardCheck($check, $officer);

        return $this->success($this->checks->hold($check, $request->validated(), $officer, $request), 'Bus held.');
    }

    public function acknowledgeHold(Request $request, FieldCheck $check): JsonResponse
    {
        $officer = $this->officer($request);
        $this->guardCheck($check, $officer);

        return $this->success($this->checks->acknowledgeHold($check, $officer, $request), 'Hold acknowledged.');
    }

    public function release(ReleaseRequest $request, FieldCheck $check): JsonResponse
    {
        $officer = $this->officer($request);
        $this->guardCheck($check, $officer);

        return $this->success($this->checks->release($check, $request->validated(), $officer, $request), 'Bus released.');
    }

    public function clear(Request $request, FieldCheck $check): JsonResponse
    {
        $officer = $this->officer($request);
        $this->guardCheck($check, $officer);

        return $this->success($this->checks->clearVehicle($check, $request->validate(['note' => ['nullable', 'string', 'max:2000']]), $officer, $request), 'Vehicle cleared.');
    }

    public function reveal(RevealRequest $request, FieldCheck $check, ManifestPassenger $passenger): JsonResponse
    {
        $officer = $this->officer($request);
        $this->guardCheck($check, $officer);

        return $this->success($this->checks->reveal(
            $check,
            $passenger,
            (array) $request->input('fields', []),
            (string) $request->string('reason'),
            $officer,
            $request,
        ), 'Identity fields revealed.');
    }

    public function addNote(NoteRequest $request, FieldCheck $check): JsonResponse
    {
        $officer = $this->officer($request);
        $this->guardCheck($check, $officer);

        return $this->success($this->checks->addNote($check, (string) $request->string('note'), $officer, $request), 'Note added.');
    }

    private function guardCheck(FieldCheck $check, Officer $officer): void
    {
        $this->guardState($check->manifest?->state_code, $officer);
    }
}
