<?php

use App\Http\Controllers\Api\V1\ManifestChecker\AccessGrantController;
use App\Http\Controllers\Api\V1\ManifestChecker\AlertController;
use App\Http\Controllers\Api\V1\ManifestChecker\AnalyticsController;
use App\Http\Controllers\Api\V1\ManifestChecker\AuditLogController;
use App\Http\Controllers\Api\V1\ManifestChecker\AuthController;
use App\Http\Controllers\Api\V1\ManifestChecker\CaseController;
use App\Http\Controllers\Api\V1\ManifestChecker\DashboardController;
use App\Http\Controllers\Api\V1\ManifestChecker\DutyPointController;
use App\Http\Controllers\Api\V1\ManifestChecker\ExportController;
use App\Http\Controllers\Api\V1\ManifestChecker\IncidentController;
use App\Http\Controllers\Api\V1\ManifestChecker\ManifestController;
use App\Http\Controllers\Api\V1\ManifestChecker\MetaController;
use App\Http\Controllers\Api\V1\ManifestChecker\NotificationController;
use App\Http\Controllers\Api\V1\ManifestChecker\OfficerController;
use App\Http\Controllers\Api\V1\ManifestChecker\WatchlistController;
use App\Http\Middleware\Npis\NpisRequestId;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/manifest-checker')->middleware([NpisRequestId::class, 'throttle:180,1'])->group(function (): void {
    Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:10,1');

    /*
    |--------------------------------------------------------------------------
    | Signed media (no bearer token so browser downloads work)
    |--------------------------------------------------------------------------
    */
    Route::get('media/exports/{export}', [ExportController::class, 'download'])
        ->middleware('signed')->name('emf.media.export');
    Route::get('media/evidence/{evidence}', [ExportController::class, 'evidence'])
        ->middleware('signed')->name('emf.media.evidence');

    Route::middleware('emf.auth')->group(function (): void {
        Route::get('auth/me', [AuthController::class, 'me']);
        Route::post('auth/logout', [AuthController::class, 'logout']);

        Route::get('meta', [MetaController::class, 'meta'])->middleware('emf.can:dashboard:read');
        Route::get('meta/states', [MetaController::class, 'states']);
        Route::get('meta/system-states', [MetaController::class, 'systemStates']);

        /*
        |----------------------------------------------------------------------
        | Dashboard and global search
        |----------------------------------------------------------------------
        */
        Route::get('dashboard', [DashboardController::class, 'index'])->middleware('emf.can:dashboard:read');
        Route::get('dashboard/search', [DashboardController::class, 'search'])->middleware('emf.can:dashboard:read');

        /*
        |----------------------------------------------------------------------
        | Live alerts
        |----------------------------------------------------------------------
        */
        Route::get('alerts', [AlertController::class, 'index'])->middleware('emf.can:alerts:read');
        Route::get('alerts/{alert}', [AlertController::class, 'show'])->middleware('emf.can:alerts:read');
        Route::post('alerts/{alert}/acknowledge', [AlertController::class, 'acknowledge'])->middleware('emf.can:alerts:respond');
        Route::post('alerts/{alert}/dismiss', [AlertController::class, 'dismiss'])->middleware('emf.can:alerts:respond');
        Route::post('alerts/{alert}/escalate', [AlertController::class, 'escalate'])->middleware('emf.can:alerts:respond');
        Route::post('alerts/{alert}/assign', [AlertController::class, 'assign'])->middleware('emf.can:alerts:respond');

        /*
        |----------------------------------------------------------------------
        | Manifests
        |----------------------------------------------------------------------
        */
        Route::get('manifests', [ManifestController::class, 'index'])->middleware('emf.can:manifests:read');
        Route::post('manifests/checks', [ManifestController::class, 'store'])->middleware('emf.can:manifests:check');
        Route::get('manifests/{manifest}', [ManifestController::class, 'show'])->middleware('emf.can:manifests:read');
        Route::get('manifests/{manifest}/passengers', [ManifestController::class, 'passengers'])->middleware('emf.can:manifests:read');
        Route::post('manifests/{manifest}/notes', [ManifestController::class, 'addNote'])->middleware('emf.can:manifests:check');
        Route::post('manifests/{manifest}/export', [ManifestController::class, 'export'])->middleware('emf.can:exports:create');

        /*
        |----------------------------------------------------------------------
        | Watchlist
        |----------------------------------------------------------------------
        */
        Route::get('watchlist', [WatchlistController::class, 'index'])->middleware('emf.can:watchlist:read');
        Route::post('watchlist', [WatchlistController::class, 'store'])->middleware('emf.can:watchlist:manage');
        Route::post('watchlist/import', [WatchlistController::class, 'import'])->middleware('emf.can:watchlist:manage');
        Route::get('watchlist/subscriptions', [WatchlistController::class, 'subscriptions'])->middleware('emf.can:watchlist:read');
        Route::post('watchlist/subscriptions', [WatchlistController::class, 'storeSubscription'])->middleware('emf.can:watchlist:manage');
        Route::delete('watchlist/subscriptions/{subscription}', [WatchlistController::class, 'destroySubscription'])->middleware('emf.can:watchlist:manage');
        Route::get('watchlist/documents', [WatchlistController::class, 'documents'])->middleware('emf.can:watchlist:read');
        Route::delete('watchlist/documents/{document}', [WatchlistController::class, 'destroyDocument'])->middleware('emf.can:watchlist:manage');
        Route::post('watchlist/{entry}/escalate', [WatchlistController::class, 'escalate'])->middleware('emf.can:watchlist:manage');
        Route::post('watchlist/{entry}/open-case', [WatchlistController::class, 'openCase'])->middleware('emf.can:watchlist:manage');
        Route::get('watchlist/{entry}', [WatchlistController::class, 'show'])->middleware('emf.can:watchlist:read');
        Route::put('watchlist/{entry}', [WatchlistController::class, 'update'])->middleware('emf.can:watchlist:manage');
        Route::delete('watchlist/{entry}', [WatchlistController::class, 'destroy'])->middleware('emf.can:watchlist:manage');
        Route::post('watchlist/{entry}/documents', [WatchlistController::class, 'storeDocument'])->middleware('emf.can:watchlist:manage');

        /*
        |----------------------------------------------------------------------
        | Incidents and cases
        |----------------------------------------------------------------------
        */
        Route::get('incidents', [IncidentController::class, 'index'])->middleware('emf.can:incidents:read');
        Route::post('incidents', [IncidentController::class, 'store'])->middleware('emf.can:incidents:manage');
        Route::get('incidents/{incident}', [IncidentController::class, 'show'])->middleware('emf.can:incidents:read');
        Route::post('incidents/{incident}/assign', [IncidentController::class, 'assign'])->middleware('emf.can:incidents:manage');
        Route::post('incidents/{incident}/convert-to-case', [IncidentController::class, 'convert'])->middleware('emf.can:incidents:manage');
        Route::post('incidents/{incident}/notes', [IncidentController::class, 'addNote'])->middleware('emf.can:incidents:manage');
        Route::post('incidents/{incident}/resolve', [IncidentController::class, 'resolve'])->middleware('emf.can:incidents:manage');

        Route::get('cases', [CaseController::class, 'index'])->middleware('emf.can:cases:read');
        Route::post('cases', [CaseController::class, 'store'])->middleware('emf.can:cases:manage');
        Route::get('cases/{case}', [CaseController::class, 'show'])->middleware('emf.can:cases:read');
        Route::put('cases/{case}', [CaseController::class, 'update'])->middleware('emf.can:cases:manage');
        Route::get('cases/{case}/activity', [CaseController::class, 'activity'])->middleware('emf.can:cases:read');
        Route::post('cases/{case}/notes', [CaseController::class, 'addNote'])->middleware('emf.can:cases:manage');
        Route::post('cases/{case}/evidence', [CaseController::class, 'addEvidence'])->middleware('emf.can:cases:manage');
        Route::post('cases/{case}/submit-closure', [CaseController::class, 'submitClosure'])->middleware('emf.can:cases:manage');
        Route::post('cases/{case}/approve-closure', [CaseController::class, 'approveClosure'])->middleware('emf.can:cases:manage');
        Route::post('cases/{case}/send-back', [CaseController::class, 'sendBack'])->middleware('emf.can:cases:manage');
        Route::post('cases/{case}/request-reopen', [CaseController::class, 'requestReopen'])->middleware('emf.can:cases:manage');
        Route::post('cases/{case}/share', [CaseController::class, 'share'])->middleware('emf.can:cases:manage');
        Route::post('cases/{case}/close', [CaseController::class, 'close'])->middleware('emf.can:cases:manage');

        /*
        |----------------------------------------------------------------------
        | Officers, devices and access grants
        |----------------------------------------------------------------------
        */
        Route::get('officers', [OfficerController::class, 'index'])->middleware('emf.can:officers:read');
        Route::get('officers/{officer}/performance', [OfficerController::class, 'performance'])->middleware('emf.can:officers:read');
        Route::post('officers/{officer}/suspend', [OfficerController::class, 'suspend'])->middleware('emf.can:officers:manage');
        Route::post('officers/{officer}/reactivate', [OfficerController::class, 'reactivate'])->middleware('emf.can:officers:manage');

        Route::get('devices', [OfficerController::class, 'devices'])->middleware('emf.can:officers:read');
        Route::post('devices/{device}/approve', [OfficerController::class, 'approveDevice'])->middleware('emf.can:officers:manage');
        Route::post('devices/{device}/block', [OfficerController::class, 'blockDevice'])->middleware('emf.can:officers:manage');

        Route::get('access-grants', [AccessGrantController::class, 'index'])->middleware('emf.can:grants:manage');
        Route::post('access-grants', [AccessGrantController::class, 'store'])->middleware('emf.can:grants:manage');
        Route::post('access-grants/conflicts', [AccessGrantController::class, 'conflicts'])->middleware('emf.can:grants:manage');
        Route::get('access-grants/{grant}', [AccessGrantController::class, 'show'])->middleware('emf.can:grants:manage');
        Route::post('access-grants/{grant}/revoke', [AccessGrantController::class, 'revoke'])->middleware('emf.can:grants:manage');

        Route::get('grant-templates', [AccessGrantController::class, 'templates'])->middleware('emf.can:grants:manage');
        Route::post('grant-templates', [AccessGrantController::class, 'storeTemplate'])->middleware('emf.can:grants:manage');
        Route::put('grant-templates/{template}', [AccessGrantController::class, 'updateTemplate'])->middleware('emf.can:grants:manage');
        Route::delete('grant-templates/{template}', [AccessGrantController::class, 'destroyTemplate'])->middleware('emf.can:grants:manage');

        /*
        |----------------------------------------------------------------------
        | Duty points and roster
        |----------------------------------------------------------------------
        */
        Route::get('duty-points', [DutyPointController::class, 'index'])->middleware('emf.can:duty:read');
        Route::post('duty-points', [DutyPointController::class, 'store'])->middleware('emf.can:duty:manage');
        Route::get('duty-points/{dutyPoint}', [DutyPointController::class, 'show'])->middleware('emf.can:duty:read');
        Route::put('duty-points/{dutyPoint}', [DutyPointController::class, 'update'])->middleware('emf.can:duty:manage');
        Route::get('duty-roster', [DutyPointController::class, 'roster'])->middleware('emf.can:duty:read');
        Route::post('duty-roster/assign', [DutyPointController::class, 'assign'])->middleware('emf.can:duty:manage');
        Route::post('duty-roster/publish', [DutyPointController::class, 'publish'])->middleware('emf.can:duty:manage');
        Route::post('duty-roster/auto-fill', [DutyPointController::class, 'autoFill'])->middleware('emf.can:duty:manage');

        /*
        |----------------------------------------------------------------------
        | Analytics, reports, audit
        |----------------------------------------------------------------------
        */
        Route::get('analytics', [AnalyticsController::class, 'overview'])->middleware('emf.can:analytics:read');
        Route::get('analytics/coverage', [AnalyticsController::class, 'coverage'])->middleware('emf.can:analytics:read');
        Route::get('analytics/units/{dutyPoint}', [AnalyticsController::class, 'unit'])->middleware('emf.can:analytics:read');
        Route::post('analytics/export', [AnalyticsController::class, 'export'])->middleware('emf.can:analytics:read');
        Route::get('analytics/schedules', [AnalyticsController::class, 'schedules'])->middleware('emf.can:analytics:read');
        Route::post('analytics/schedules', [AnalyticsController::class, 'storeSchedule'])->middleware('emf.can:analytics:read');

        Route::get('audit-logs', [AuditLogController::class, 'index'])->middleware('emf.can:audit:read');
        Route::post('audit-logs/export', [AuditLogController::class, 'export'])->middleware('emf.can:audit:read');

        Route::get('exports/{export}', [ExportController::class, 'show'])->middleware('emf.can:exports:create');

        /*
        |----------------------------------------------------------------------
        | Notifications and settings
        |----------------------------------------------------------------------
        */
        Route::get('notifications', [NotificationController::class, 'inbox']);
        Route::post('notifications/read-all', [NotificationController::class, 'readAll']);
        Route::post('notifications/{notification}/read', [NotificationController::class, 'read']);

        Route::get('settings', [NotificationController::class, 'settings']);
        Route::put('settings/profile', [NotificationController::class, 'updateProfile']);
        Route::put('settings/notifications', [NotificationController::class, 'updatePreferences']);
        Route::get('settings/sessions', [NotificationController::class, 'sessions']);
        Route::delete('settings/sessions/{session}', [NotificationController::class, 'revokeSession']);
        Route::get('settings/help', [NotificationController::class, 'help']);
    });
});
