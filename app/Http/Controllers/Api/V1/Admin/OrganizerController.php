<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\NpisApiController;
use App\Http\Requests\Npis\StoreOrganizerRequest;
use App\Models\Npis\AdminUser;
use App\Services\Npis\OrganizerManagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrganizerController extends NpisApiController
{
    public function __construct(private readonly OrganizerManagementService $organizers) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:200'],
            'role' => ['nullable', 'string', Rule::in(array_keys((array) config('npis.organizer.roles', [])))],
            'isActive' => ['nullable', 'boolean'],
            'sort' => ['nullable', 'string', Rule::in(['name', '-name', 'createdAt', '-createdAt', 'lastLoginAt', '-lastLoginAt'])],
            'page' => ['nullable', 'integer', 'min:1'],
            'pageSize' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $result = $this->organizers->paginate($filters);

        return $this->success([
            'data' => $result['organizers']
                ->map(fn (AdminUser $organizer): array => $this->organizers->contract($organizer))
                ->all(),
            'pagination' => $result['pagination'],
        ], 'Organizers retrieved successfully');
    }

    public function options(): JsonResponse
    {
        return $this->success(
            $this->organizers->options(),
            'Organizer options retrieved successfully'
        );
    }

    public function store(StoreOrganizerRequest $request): JsonResponse
    {
        $organizer = $this->organizers->create($this->admin($request), $request->validated());

        $credentialsEmailed = $this->organizers->sendCredentials($organizer, (string) $request->validated('password'));

        return $this->success([
            ...$this->organizers->contract($organizer),
            'credentialsEmailed' => $credentialsEmailed,
        ], 'Organizer created successfully', 201);
    }
}
