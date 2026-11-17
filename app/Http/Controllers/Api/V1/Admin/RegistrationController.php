<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Exceptions\NpisApiException;
use App\Http\Controllers\Api\V1\NpisApiController;
use App\Http\Requests\Npis\AccommodationAllocationRequest;
use App\Http\Requests\Npis\BadgeCategoryRequest;
use App\Http\Requests\Npis\PhotoRequest;
use App\Http\Requests\Npis\RegistrationIndexRequest;
use App\Http\Requests\Npis\ReviewRegistrationRequest;
use App\Http\Resources\Npis\RegistrationResource;
use App\Models\Npis\Registration;
use App\Services\Npis\AuditService;
use App\Services\Npis\BadgeService;
use App\Services\Npis\MediaService;
use App\Services\Npis\RegistrationAdminService;
use App\Services\Npis\RegistrationSearchService;
use App\Services\Npis\RegistrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class RegistrationController extends NpisApiController
{
    public function __construct(
        private readonly RegistrationSearchService $search,
        private readonly RegistrationAdminService $registrations,
        private readonly BadgeService $badges,
        private readonly MediaService $media,
        private readonly AuditService $audit,
    ) {}

    public function index(RegistrationIndexRequest $request): JsonResponse
    {
        $result = $this->search->paginate($request->validated(), $this->admin($request));

        return $this->success([
            'data' => RegistrationResource::collection($result['registrations'])->resolve($request),
            'pagination' => $result['pagination'],
        ], 'Registrations retrieved successfully');
    }

    public function show(Request $request, string $registrationId): JsonResponse
    {
        $registration = $this->find($registrationId);

        return $this->success(
            new RegistrationResource($registration)->resolve($request),
            'Registration retrieved successfully'
        );
    }

    public function review(ReviewRegistrationRequest $request, string $registrationId): JsonResponse
    {
        $registration = $this->find($registrationId);
        Gate::forUser($this->admin($request))->authorize('review', $registration);

        return $this->success(
            new RegistrationResource(
                $this->registrations->review($this->admin($request), $registration, $request->validated())
            )->resolve($request),
            'Registration reviewed successfully'
        );
    }

    public function badge(BadgeCategoryRequest $request, string $registrationId): JsonResponse
    {
        $registration = $this->find($registrationId);
        $before = $this->badges->effectiveCode($registration);
        $this->badges->assign($registration, $request->validated('category'), $this->admin($request));
        $after = $this->badges->effectiveCode($registration->fresh(['profile', 'badge']));

        $this->audit->record(
            $this->admin($request),
            'registration.badge_assigned',
            'registration',
            $registration->id,
            'Tag category '.$before.' → '.$after.' for '.$registration->profile?->full_name.' ('.$registration->summit_tag_number.')',
            ['before' => $before, 'after' => $after, 'assigned' => $after !== $this->badges->derivedCode($registration), 'note' => $request->validated('note')],
            $request,
        );

        $registration = $registration->fresh();
        $registration->version += 1;
        $registration->save();

        return $this->success(
            new RegistrationResource($registration->fresh(app(RegistrationService::class)->responseRelations()))->resolve($request),
            'Badge category updated successfully'
        );
    }

    public function photo(PhotoRequest $request, string $registrationId): JsonResponse
    {
        $registration = $this->find($registrationId);
        $photo = $request->validated('photo');

        if ($photo === null) {
            $this->media->removePhoto($registration);
        } else {
            $this->media->storePhoto($registration, (string) $photo, 'desk');
        }

        $registration->version += 1;
        $registration->save();

        $this->audit->record(
            $this->admin($request),
            $photo === null ? 'registration.photo_removed' : 'registration.photo_taken',
            'registration',
            $registration->id,
            ($photo === null ? 'Removed tag photo' : 'Took tag photo').' for '.$registration->profile?->full_name.' ('.$registration->summit_tag_number.')',
            [],
            $request,
        );

        return $this->success(
            new RegistrationResource($registration->fresh(app(RegistrationService::class)->responseRelations()))->resolve($request),
            $photo === null ? 'Registration photo removed successfully' : 'Registration photo updated successfully'
        );
    }

    public function allocation(AccommodationAllocationRequest $request, string $registrationId): JsonResponse
    {
        $registration = $this->find($registrationId);

        return $this->success(
            new RegistrationResource(
                $this->registrations->allocate($this->admin($request), $registration, $request->validated())
            )->resolve($request),
            'Accommodation allocation updated successfully'
        );
    }

    private function find(string $registrationId): Registration
    {
        $registration = Registration::query()
            ->with(app(RegistrationService::class)->responseRelations())
            ->find($registrationId);

        if (! $registration) {
            throw NpisApiException::make(404, 'NOT_FOUND', 'Registration not found.');
        }

        return $registration;
    }
}
