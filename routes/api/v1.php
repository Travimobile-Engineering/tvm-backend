<?php

use App\Http\Controllers\Api\V1\Admin\AccommodationController as AdminAccommodationController;
use App\Http\Controllers\Api\V1\Admin\AuditController as AdminAuditController;
use App\Http\Controllers\Api\V1\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Api\V1\Admin\CertificateController as AdminCertificateController;
use App\Http\Controllers\Api\V1\Admin\CheckInController as AdminCheckInController;
use App\Http\Controllers\Api\V1\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Api\V1\Admin\EventController as AdminEventController;
use App\Http\Controllers\Api\V1\Admin\ExportController as AdminExportController;
use App\Http\Controllers\Api\V1\Admin\FaqController as AdminFaqController;
use App\Http\Controllers\Api\V1\Admin\RegistrationController as AdminRegistrationController;
use App\Http\Controllers\Api\V1\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Api\V1\Admin\TrainingController as AdminTrainingController;
use App\Http\Controllers\Api\V1\EventController;
use App\Http\Controllers\Api\V1\MediaController;
use App\Http\Controllers\Api\V1\NotificationWebhookController;
use App\Http\Controllers\Api\V1\ReferenceController;
use App\Http\Controllers\Api\V1\RegistrationController;
use App\Http\Controllers\Api\V1\VerificationController;
use App\Http\Middleware\Npis\EnsureAccessGrant;
use App\Http\Middleware\Npis\EnsureIdempotencyKey;
use App\Http\Middleware\Npis\EnsureOrganizerSession;
use App\Http\Middleware\Npis\EnsureParticipantSession;
use App\Http\Middleware\Npis\EnsurePermission;
use App\Http\Middleware\Npis\NpisRequestId;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware([NpisRequestId::class])->group(function (): void {
    /*
    |--------------------------------------------------------------------------
    | Public event and reference data
    |--------------------------------------------------------------------------
    */
    Route::get('event', [EventController::class, 'show']);
    Route::get('event/faqs', [EventController::class, 'faqs']);

    Route::prefix('reference')->group(function (): void {
        Route::get('ranks', [ReferenceController::class, 'ranks']);
        Route::get('states', [ReferenceController::class, 'states']);
        Route::get('commands', [ReferenceController::class, 'commands']);
        Route::get('units', [ReferenceController::class, 'units']);
        Route::get('airports', [ReferenceController::class, 'airports']);
        Route::get('banks', [ReferenceController::class, 'banks']);
        Route::get('protocol', [ReferenceController::class, 'protocol']);
    });

    /*
    |--------------------------------------------------------------------------
    | Phone / email verification
    |--------------------------------------------------------------------------
    */
    Route::post('verifications', [VerificationController::class, 'store']);
    Route::get('verifications/{verificationId}', [VerificationController::class, 'show']);
    Route::post('verifications/{verificationId}/resend', [VerificationController::class, 'resend']);
    Route::post('verifications/{verificationId}/confirm', [VerificationController::class, 'confirm']);

    /*
    |--------------------------------------------------------------------------
    | Participant registration
    |--------------------------------------------------------------------------
    */
    Route::middleware(EnsureParticipantSession::class)->group(function (): void {
        Route::post('registrations', [RegistrationController::class, 'store'])
            ->middleware(EnsureIdempotencyKey::class);
        Route::get('registrations/me', [RegistrationController::class, 'me']);
        Route::put('registrations/me/photo', [RegistrationController::class, 'photo']);
        Route::put('registrations/me/itinerary', [RegistrationController::class, 'itinerary']);
        Route::put('registrations/me/accommodation', [RegistrationController::class, 'accommodation']);
        Route::get('registrations/me/schedule', [RegistrationController::class, 'schedule']);
        Route::get('registrations/me/certificate', [RegistrationController::class, 'certificate']);
    });

    /*
    |--------------------------------------------------------------------------
    | Organizer authentication
    |--------------------------------------------------------------------------
    */
    Route::prefix('admin')->group(function (): void {
        Route::post('access', [AdminAuthController::class, 'access']);

        Route::post('session', [AdminAuthController::class, 'session'])
            ->middleware(EnsureAccessGrant::class);

        Route::put('access-code', [AdminAuthController::class, 'rotateAccessCode'])
            ->middleware([EnsureOrganizerSession::class, EnsurePermission::class.':event:manage']);

        Route::middleware(EnsureOrganizerSession::class)->group(function (): void {
            /*
            |------------------------------------------------------------------
            | Organizer session and dashboard
            |------------------------------------------------------------------
            */
            Route::get('me', [AdminAuthController::class, 'me']);

            Route::get('dashboard', [AdminDashboardController::class, 'index'])
                ->middleware(EnsurePermission::class.':registrations:read');

            Route::get('event', [AdminEventController::class, 'settings'])
                ->middleware(EnsurePermission::class.':event:manage');

            Route::get('hotels', [AdminAccommodationController::class, 'index'])
                ->middleware(EnsurePermission::class.':accommodation:read');

            /*
            |------------------------------------------------------------------
            | Participant records
            |------------------------------------------------------------------
            */
            Route::get('registrations', [AdminRegistrationController::class, 'index'])
                ->middleware(EnsurePermission::class.':registrations:read');
            Route::get('registrations/{registrationId}', [AdminRegistrationController::class, 'show'])
                ->middleware(EnsurePermission::class.':registrations:read');
            Route::post('registrations/{registrationId}/review', [AdminRegistrationController::class, 'review'])
                ->middleware(EnsurePermission::class.':registrations:review');
            Route::put('registrations/{registrationId}/badge', [AdminRegistrationController::class, 'badge'])
                ->middleware(EnsurePermission::class.':registrations:review');
            Route::put('registrations/{registrationId}/photo', [AdminRegistrationController::class, 'photo'])
                ->middleware(EnsurePermission::class.':registrations:review');
            Route::put('registrations/{registrationId}/accommodation-allocation', [AdminRegistrationController::class, 'allocation'])
                ->middleware(EnsurePermission::class.':accommodation:allocate');

            /*
            |------------------------------------------------------------------
            | Reports
            |------------------------------------------------------------------
            */
            Route::get('reports/registration', [AdminReportController::class, 'registration'])
                ->middleware(EnsurePermission::class.':registrations:read');
            Route::get('reports/travel', [AdminReportController::class, 'travel'])
                ->middleware(EnsurePermission::class.':travel:read');
            Route::get('reports/accommodation', [AdminReportController::class, 'accommodation'])
                ->middleware(EnsurePermission::class.':accommodation:read');
            Route::get('reports/training', [AdminReportController::class, 'training'])
                ->middleware(EnsurePermission::class.':training:read');
            Route::get('reports/attendance', [AdminReportController::class, 'attendance'])
                ->middleware(EnsurePermission::class.':attendance:read');

            /*
            |------------------------------------------------------------------
            | Training
            |------------------------------------------------------------------
            */
            Route::get('training/sessions', [AdminTrainingController::class, 'sessions'])
                ->middleware(EnsurePermission::class.':training:read');
            Route::get('training/sessions/{sessionId}/assignments', [AdminTrainingController::class, 'assignments'])
                ->middleware(EnsurePermission::class.':training:read');
            Route::post('training/sessions', [AdminTrainingController::class, 'store'])
                ->middleware(EnsurePermission::class.':training:assign');
            Route::put('training/sessions/{sessionId}', [AdminTrainingController::class, 'update'])
                ->middleware(EnsurePermission::class.':training:assign');
            Route::delete('training/sessions/{sessionId}', [AdminTrainingController::class, 'destroy'])
                ->middleware(EnsurePermission::class.':training:assign');
            Route::post('training/sessions/{sessionId}/assignments', [AdminTrainingController::class, 'assign'])
                ->middleware(EnsurePermission::class.':training:assign');
            Route::delete('training/assignments/{assignmentId}', [AdminTrainingController::class, 'removeAssignment'])
                ->middleware(EnsurePermission::class.':training:assign');

            /*
            |------------------------------------------------------------------
            | Attendance / check-in
            |------------------------------------------------------------------
            */
            Route::post('check-ins', [AdminCheckInController::class, 'store'])
                ->middleware([EnsurePermission::class.':attendance:record', EnsureIdempotencyKey::class]);
            Route::get('check-ins', [AdminCheckInController::class, 'index'])
                ->middleware(EnsurePermission::class.':attendance:read');

            /*
            |------------------------------------------------------------------
            | Certificates
            |------------------------------------------------------------------
            */
            Route::post('certificates', [AdminCertificateController::class, 'store'])
                ->middleware([EnsurePermission::class.':certificates:manage', EnsureIdempotencyKey::class]);
            Route::get('certificates', [AdminCertificateController::class, 'index'])
                ->middleware(EnsurePermission::class.':certificates:manage');
            Route::post('certificates/{certificateId}/deliveries', [AdminCertificateController::class, 'retry'])
                ->middleware([EnsurePermission::class.':certificates:manage', EnsureIdempotencyKey::class]);

            /*
            |------------------------------------------------------------------
            | Exports
            |------------------------------------------------------------------
            */
            Route::post('exports', [AdminExportController::class, 'store'])
                ->middleware([EnsurePermission::class.':exports:create', EnsureIdempotencyKey::class]);
            Route::get('exports/{exportId}', [AdminExportController::class, 'show'])
                ->middleware(EnsurePermission::class.':exports:create');

            /*
            |------------------------------------------------------------------
            | Audit
            |------------------------------------------------------------------
            */
            Route::get('audit-events', [AdminAuditController::class, 'index'])
                ->middleware(EnsurePermission::class.':audit:read');

            /*
            |------------------------------------------------------------------
            | Event and FAQ management
            |------------------------------------------------------------------
            */
            Route::put('event', [AdminEventController::class, 'update'])
                ->middleware(EnsurePermission::class.':event:manage');
            Route::get('faqs', [AdminFaqController::class, 'index'])
                ->middleware(EnsurePermission::class.':event:manage');
            Route::put('faqs', [AdminFaqController::class, 'update'])
                ->middleware(EnsurePermission::class.':event:manage');
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Notification provider callbacks
    |--------------------------------------------------------------------------
    */
    Route::post('webhooks/notifications/{provider}', NotificationWebhookController::class);

    /*
    |--------------------------------------------------------------------------
    | Private media (signed URLs only)
    |--------------------------------------------------------------------------
    */
    Route::get('media/photos/{registration}', [MediaController::class, 'photo'])->name('npis.media.photo');
    Route::get('media/certificates/{certificate}', [MediaController::class, 'certificate'])->name('npis.media.certificate');
    Route::get('media/exports/{export}', [MediaController::class, 'export'])->name('npis.media.export');
});
