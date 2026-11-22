<?php

namespace App\Http\Controllers\Api\V1\ManifestChecker;

use App\Http\Requests\Emf\NotificationPreferencesRequest;
use App\Http\Requests\Emf\ProfileUpdateRequest;
use App\Models\Emf\Notification;
use App\Services\Emf\EmfNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends EmfApiController
{
    public function __construct(private readonly EmfNotificationService $notifications) {}

    public function inbox(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'filter' => ['sometimes', 'in:all,needs_action,alerts,approvals,expiries'],
        ]);

        return $this->success($this->notifications->inbox($this->officer($request), $validated['filter'] ?? null), 'Notifications.');
    }

    public function read(Request $request, Notification $notification): JsonResponse
    {
        $this->notifications->markRead($notification, $this->officer($request));

        return $this->success(['notificationId' => $notification->id, 'status' => 'read'], 'Notification marked read.');
    }

    public function readAll(Request $request): JsonResponse
    {
        $count = $this->notifications->markAllRead($this->officer($request));

        return $this->success(['markedRead' => $count], 'All notifications marked read.');
    }

    public function settings(Request $request): JsonResponse
    {
        return $this->success($this->notifications->settings($this->officer($request)), 'Settings.');
    }

    public function updateProfile(ProfileUpdateRequest $request): JsonResponse
    {
        $this->notifications->updateProfile($this->officer($request), $request->validated(), $request);

        return $this->success($this->notifications->settings($this->officer($request)), 'Profile updated.');
    }

    public function updatePreferences(NotificationPreferencesRequest $request): JsonResponse
    {
        $preferences = $this->notifications->updatePreferences(
            $this->officer($request),
            (array) $request->input('preferences', []),
            $request,
        );

        return $this->success(['notifications' => $preferences], 'Notification preferences updated.');
    }

    public function sessions(Request $request): JsonResponse
    {
        return $this->success($this->notifications->sessions($this->officer($request)), 'Sessions and devices.');
    }

    public function revokeSession(Request $request, string $session): JsonResponse
    {
        $this->notifications->revokeSession($this->officer($request), $session, $request);

        return $this->success(null, 'Session revoked.');
    }

    public function help(): JsonResponse
    {
        return $this->success($this->notifications->help(), 'Help and support.');
    }
}
