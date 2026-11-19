<?php

namespace App\Http\Controllers\Api\V1\ManifestChecker;

use App\Exceptions\NpisApiException;
use App\Http\Requests\Emf\CaseApproveRequest;
use App\Http\Requests\Emf\CaseCloseRequest;
use App\Http\Requests\Emf\CaseClosureRequest;
use App\Http\Requests\Emf\CaseEvidenceRequest;
use App\Http\Requests\Emf\CaseNoteRequest;
use App\Http\Requests\Emf\CaseReopenRequest;
use App\Http\Requests\Emf\CaseSendBackRequest;
use App\Http\Requests\Emf\CaseShareRequest;
use App\Http\Requests\Emf\CaseStoreRequest;
use App\Http\Requests\Emf\CaseUpdateRequest;
use App\Models\Emf\CaseFile;
use App\Services\Emf\EmfCaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CaseController extends EmfApiController
{
    public function __construct(private readonly EmfCaseService $cases) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'status' => ['sometimes', 'in:open,in_review,awaiting_approval,closed'],
            'severity' => ['sometimes', 'in:critical,high,medium,low'],
            'assignee' => ['sometimes', 'string', 'max:64'],
            'search' => ['sometimes', 'string', 'max:120'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'pageSize' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $result = $this->cases->paginate($filters);

        return $this->success([
            'board' => $result['board'],
            'data' => $result['data'],
            'pagination' => $result['pagination'],
        ], 'Cases.');
    }

    public function store(CaseStoreRequest $request): JsonResponse
    {
        $case = $this->cases->create($request->validated(), $this->officer($request), $request);

        return $this->success($this->cases->show($case), 'Case created.', 201);
    }

    public function show(CaseFile $case): JsonResponse
    {
        return $this->success($this->cases->show($case), 'Case workspace.');
    }

    public function update(CaseUpdateRequest $request, CaseFile $case): JsonResponse
    {
        $case = $this->cases->update($case, $request->validated(), $this->officer($request), $request);

        return $this->success($this->cases->show($case), 'Case updated.');
    }

    public function submitClosure(CaseClosureRequest $request, CaseFile $case): JsonResponse
    {
        $case = $this->cases->submitClosure($case, $request->validated(), $this->officer($request), $request);

        return $this->success($this->cases->show($case), 'Case submitted for closure approval.');
    }

    public function approveClosure(CaseApproveRequest $request, CaseFile $case): JsonResponse
    {
        $case = $this->cases->approveClosure($case, $request->validated(), $this->officer($request), $request);

        return $this->success($this->cases->show($case), 'Case closure approved.');
    }

    public function sendBack(CaseSendBackRequest $request, CaseFile $case): JsonResponse
    {
        $case = $this->cases->sendBack($case, $request->validated(), $this->officer($request), $request);

        return $this->success($this->cases->show($case), 'Case sent back to the owner.');
    }

    public function requestReopen(CaseReopenRequest $request, CaseFile $case): JsonResponse
    {
        $case = $this->cases->requestReopen($case, $request->validated(), $this->officer($request), $request);

        return $this->success($this->cases->show($case), 'Case reopen requested.');
    }

    public function share(CaseShareRequest $request, CaseFile $case): JsonResponse
    {
        $case = $this->cases->share($case, $request->validated(), $this->officer($request), $request);

        return $this->success($this->cases->show($case), 'Case shared.');
    }

    public function close(CaseCloseRequest $request, CaseFile $case): JsonResponse
    {
        $case = $this->cases->close($case, $request->validated(), $this->officer($request), $request);

        return $this->success($this->cases->show($case), 'Case closed.');
    }

    public function addNote(CaseNoteRequest $request, CaseFile $case): JsonResponse
    {
        $this->cases->addNote($case, (string) $request->string('note'), $this->officer($request), $request);

        return $this->success($this->cases->show($case->fresh()), 'Case note added.');
    }

    public function addEvidence(CaseEvidenceRequest $request, CaseFile $case): JsonResponse
    {
        $validated = $request->validated();
        $fileUrl = $validated['fileUrl'] ?? null;
        $fileSize = $validated['fileSize'] ?? null;

        if ($request->hasFile('file')) {
            $upload = uploadFile($request, 'file', 'emf/cases');
            $fileUrl = $upload['url'];
            $fileSize = $request->file('file')?->getSize();
        }

        if (! $fileUrl) {
            throw NpisApiException::validation('Upload a file or provide a file URL.', ['file' => 'Required']);
        }

        $this->cases->addEvidence($case, $validated + ['fileSize' => $fileSize], $fileUrl, $this->officer($request), $request);

        return $this->success($this->cases->show($case->fresh()), 'Evidence added.', 201);
    }

    public function activity(CaseFile $case): JsonResponse
    {
        $data = $this->cases->show($case);

        return $this->success([
            'activity' => $data['activity'],
            'evidence' => $data['evidence'],
        ], 'Case activity and evidence.');
    }
}
