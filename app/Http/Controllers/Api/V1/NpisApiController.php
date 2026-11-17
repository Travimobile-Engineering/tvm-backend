<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\NpisApiException;
use App\Http\Controllers\Controller;
use App\Models\Npis\AdminUser;
use App\Models\Npis\Registration;
use App\Services\Npis\RegistrationService;
use App\Services\Npis\VerificationService;
use App\Trait\HttpResponse;
use Illuminate\Http\Request;

abstract class NpisApiController extends Controller
{
    use HttpResponse;

    protected function participantRegistration(Request $request): Registration
    {
        $contact = $request->attributes->get('npis_contact');

        $registration = $contact
            ? app(VerificationService::class)->resolveRegistration($contact)
            : null;

        if (! $registration || $registration->status?->value === 'cancelled') {
            throw NpisApiException::make(404, 'NOT_FOUND', 'No registration was found for this phone number or email. You can register now.');
        }

        return $registration->load(app(RegistrationService::class)->responseRelations());
    }

    protected function admin(Request $request): AdminUser
    {
        $admin = $request->attributes->get('npis_admin');

        if (! $admin) {
            throw NpisApiException::make(401, 'UNAUTHENTICATED', 'Sign in to the organizer dashboard.');
        }

        return $admin;
    }
}
