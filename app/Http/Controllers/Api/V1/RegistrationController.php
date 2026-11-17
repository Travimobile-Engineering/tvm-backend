<?php

namespace App\Http\Controllers\Api\V1;

use App\Enum\Npis\RegistrationStatus;
use App\Http\Requests\Npis\PhotoRequest;
use App\Http\Requests\Npis\StoreRegistrationRequest;
use App\Http\Requests\Npis\UpdateAccommodationRequest;
use App\Http\Requests\Npis\UpdateItineraryRequest;
use App\Http\Resources\Npis\RegistrationResource;
use App\Services\Npis\AuditService;
use App\Services\Npis\CertificateService;
use App\Services\Npis\MediaService;
use App\Services\Npis\RegistrationService;
use App\Services\Npis\TrainingService;
use App\Services\Npis\VerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RegistrationController extends NpisApiController
{
    public function __construct(
        private readonly RegistrationService $registrations,
        private readonly VerificationService $verifications,
        private readonly MediaService $media,
        private readonly AuditService $audit,
        private readonly TrainingService $training,
        private readonly CertificateService $certificates,
    ) {}

    public function store(StoreRegistrationRequest $request): JsonResponse
    {
        $contact = $request->attributes->get('npis_contact');
        $existing = $contact ? $this->verifications->resolveRegistration($contact) : null;
        $registration = $this->registrations->submit($contact, $request->validated());

        return $this->success(
            new RegistrationResource($registration)->resolve($request),
            'Registration submitted successfully',
            $existing?->status === RegistrationStatus::Incomplete ? 200 : 201
        );
    }

    public function me(Request $request): JsonResponse
    {
        return $this->success(
            new RegistrationResource($this->participantRegistration($request))->resolve($request),
            'Registration retrieved successfully'
        );
    }

    public function photo(PhotoRequest $request): JsonResponse
    {
        $registration = $this->participantRegistration($request);
        $photo = $request->validated('photo');

        if ($photo === null) {
            $this->media->removePhoto($registration);
        } else {
            $this->media->storePhoto($registration, (string) $photo, 'registrant');
        }

        $registration->version += 1;
        $registration->save();

        $this->audit->record(
            $registration,
            $photo === null ? 'registration.photo_removed' : 'registration.photo_updated',
            'registration',
            $registration->id,
            ($photo === null ? 'Removed tag photo' : 'Tag photo added').' by '.$registration->profile?->full_name,
            [],
            $request,
        );

        return $this->success(
            new RegistrationResource($registration->fresh($this->registrations->responseRelations()))->resolve($request),
            $photo === null ? 'Registration photo removed successfully' : 'Registration photo updated successfully'
        );
    }

    public function itinerary(UpdateItineraryRequest $request): JsonResponse
    {
        $registration = $this->participantRegistration($request);
        $updated = $this->registrations->updateItinerary(
            $registration,
            $request->validated(),
            $request->header('If-Match'),
        );

        return $this->success(
            new RegistrationResource($updated)->resolve($request),
            'Travel itinerary updated successfully'
        );
    }

    public function accommodation(UpdateAccommodationRequest $request): JsonResponse
    {
        $registration = $this->participantRegistration($request);
        $updated = $this->registrations->updateAccommodation(
            $registration,
            $request->validated(),
            $request->header('If-Match'),
        );

        return $this->success(
            new RegistrationResource($updated)->resolve($request),
            'Accommodation updated successfully'
        );
    }

    public function schedule(Request $request): JsonResponse
    {
        return $this->success(
            $this->training->scheduleFor($this->participantRegistration($request)),
            'Schedule retrieved successfully'
        );
    }

    public function certificate(Request $request): JsonResponse
    {
        return $this->success(
            $this->certificates->participantCertificate($this->participantRegistration($request)),
            'Certificate retrieved successfully'
        );
    }
}
