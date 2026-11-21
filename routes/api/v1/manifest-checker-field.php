<?php

use App\Http\Controllers\Api\V1\ManifestChecker\Field\AlertController;
use App\Http\Controllers\Api\V1\ManifestChecker\Field\CheckController;
use App\Http\Controllers\Api\V1\ManifestChecker\Field\HomeController;
use App\Http\Controllers\Api\V1\ManifestChecker\Field\ProfileController;
use App\Http\Controllers\Api\V1\ManifestChecker\Field\ReportController;
use App\Http\Middleware\Npis\NpisRequestId;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/manifest-checker/field')->middleware([NpisRequestId::class, 'throttle:180,1'])->group(function (): void {
    Route::get('media/attachments/{attachment}', [ReportController::class, 'downloadAttachment'])
        ->middleware('signed')->name('emf.field.media.attachment');

    Route::middleware('emf.auth')->group(function (): void {
        Route::get('home', [HomeController::class, 'index'])->middleware('emf.can:manifests:read');
        Route::get('search', [HomeController::class, 'search'])->middleware('emf.can:manifests:read');
        Route::get('manifests/upcoming', [HomeController::class, 'upcoming'])->middleware('emf.can:manifests:read');
        Route::get('manifests/recent', [HomeController::class, 'recent'])->middleware('emf.can:manifests:read');
        Route::get('manifests/{manifest}/upcoming', [HomeController::class, 'upcomingManifest'])->middleware('emf.can:manifests:read');

        Route::post('manifests/{manifest}/checks', [CheckController::class, 'start'])->middleware('emf.can:manifests:check');
        Route::get('checks/{check}', [CheckController::class, 'show'])->middleware('emf.can:manifests:read');
        Route::get('checks/{check}/record', [CheckController::class, 'record'])->middleware('emf.can:manifests:read');
        Route::post('checks/{check}/seats/{passenger}/confirm', [CheckController::class, 'confirm'])->middleware('emf.can:manifests:check');
        Route::post('checks/{check}/seats/{passenger}/flag', [CheckController::class, 'flag'])->middleware('emf.can:manifests:check');
        Route::post('checks/{check}/seats/{passenger}/resolve', [CheckController::class, 'resolve'])->middleware('emf.can:manifests:check');
        Route::post('checks/{check}/seats/{passenger}/escalate', [CheckController::class, 'escalate'])->middleware('emf.can:manifests:check');
        Route::post('checks/{check}/seats/{passenger}/reveal', [CheckController::class, 'reveal'])->middleware('emf.can:manifests:check');
        Route::post('checks/{check}/hold', [CheckController::class, 'hold'])->middleware('emf.can:manifests:check');
        Route::post('checks/{check}/hold/acknowledge', [CheckController::class, 'acknowledgeHold'])->middleware('emf.can:manifests:check');
        Route::post('checks/{check}/release', [CheckController::class, 'release'])->middleware('emf.can:manifests:check');
        Route::post('checks/{check}/clear-vehicle', [CheckController::class, 'clear'])->middleware('emf.can:manifests:check');
        Route::post('checks/{check}/notes', [CheckController::class, 'addNote'])->middleware('emf.can:manifests:check');

        Route::get('alerts', [AlertController::class, 'index'])->middleware('emf.can:alerts:read');
        Route::get('alerts/{alert}', [AlertController::class, 'show'])->middleware('emf.can:alerts:read');
        Route::post('alerts/{alert}/acknowledge', [AlertController::class, 'acknowledge'])->middleware('emf.can:alerts:respond');
        Route::post('alerts/{alert}/clear', [AlertController::class, 'clear'])->middleware('emf.can:alerts:respond');

        Route::get('reports', [ReportController::class, 'index'])->middleware('emf.can:incidents:read');
        Route::get('reports/meta', [ReportController::class, 'meta'])->middleware('emf.can:incidents:read');
        Route::post('reports', [ReportController::class, 'store'])->middleware('emf.can:incidents:manage');
        Route::post('reports/sync', [ReportController::class, 'sync'])->middleware('emf.can:incidents:manage');
        Route::get('reports/{report}', [ReportController::class, 'show'])->middleware('emf.can:incidents:read');
        Route::post('reports/{report}/notes', [ReportController::class, 'addNote'])->middleware('emf.can:incidents:manage');
        Route::post('reports/{report}/sign', [ReportController::class, 'sign'])->middleware('emf.can:incidents:manage');
        Route::post('reports/{report}/attachments', [ReportController::class, 'addAttachment'])->middleware('emf.can:incidents:manage');

        Route::get('profile', [ProfileController::class, 'show']);
        Route::put('profile/security', [ProfileController::class, 'updateSecurity']);
        Route::put('profile/appearance', [ProfileController::class, 'updateAppearance']);
        Route::put('profile/notifications', [ProfileController::class, 'updateNotifications']);
        Route::post('profile/password', [ProfileController::class, 'changePassword']);
        Route::get('profile/sessions', [ProfileController::class, 'sessions']);
        Route::delete('profile/sessions/others', [ProfileController::class, 'revokeOtherSessions']);
        Route::delete('profile/sessions/{session}', [ProfileController::class, 'revokeSession']);
        Route::post('profile/logout-all', [ProfileController::class, 'logoutAll']);
        Route::get('profile/activity', [ProfileController::class, 'activity']);
        Route::get('support', [ProfileController::class, 'support']);
        Route::post('support', [ProfileController::class, 'createSupport']);
        Route::post('profile/correction-request', [ProfileController::class, 'requestCorrection']);
    });
});
