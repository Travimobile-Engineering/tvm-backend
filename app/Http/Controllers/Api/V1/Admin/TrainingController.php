<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Exceptions\NpisApiException;
use App\Http\Controllers\Api\V1\NpisApiController;
use App\Http\Requests\Npis\TrainingAssignmentRequest;
use App\Http\Requests\Npis\TrainingSessionRequest;
use App\Models\Npis\TrainingAssignment;
use App\Models\Npis\TrainingSession;
use App\Services\Npis\TrainingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TrainingController extends NpisApiController
{
    public function __construct(private readonly TrainingService $training) {}

    public function sessions(): JsonResponse
    {
        return $this->success($this->training->sessions(), 'Training sessions retrieved successfully');
    }

    public function store(TrainingSessionRequest $request): JsonResponse
    {
        return $this->success(
            $this->training->contract($this->training->createSession($this->admin($request), $request->validated())),
            'Training session created successfully',
            201
        );
    }

    public function update(TrainingSessionRequest $request, string $sessionId): JsonResponse
    {
        $session = TrainingSession::query()->find($sessionId);

        if (! $session) {
            throw NpisApiException::make(404, 'NOT_FOUND', 'Training session not found.');
        }

        return $this->success(
            $this->training->contract($this->training->updateSession($this->admin($request), $session, $request->validated())),
            'Training session updated successfully'
        );
    }

    public function destroy(Request $request, string $sessionId): JsonResponse
    {
        $session = TrainingSession::query()->find($sessionId);

        if (! $session) {
            throw NpisApiException::make(404, 'NOT_FOUND', 'Training session not found.');
        }

        $this->training->deleteSession($this->admin($request), $session);

        return $this->success(null, 'Training session deleted successfully', 204);
    }

    public function assign(TrainingAssignmentRequest $request, string $sessionId): JsonResponse
    {
        $session = TrainingSession::query()->find($sessionId);

        if (! $session) {
            throw NpisApiException::make(404, 'NOT_FOUND', 'Training session not found.');
        }

        return $this->success(
            $this->training->assign($this->admin($request), $session, $request->validated('registrationIds')),
            'Training assignments processed successfully'
        );
    }

    public function removeAssignment(Request $request, string $assignmentId): JsonResponse
    {
        $assignment = TrainingAssignment::query()->find($assignmentId);

        if (! $assignment) {
            throw NpisApiException::make(404, 'NOT_FOUND', 'Assignment not found.');
        }

        $this->training->removeAssignment($this->admin($request), $assignment);

        return $this->success(null, 'Training assignment removed successfully', 204);
    }
}
