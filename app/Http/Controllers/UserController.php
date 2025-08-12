<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\NotificationRequest;
use App\Http\Requests\SaveFCMTokenRequest;
use App\Services\UserService;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(
        protected UserService $service,
    )
    {}

    public function changePassword(ChangePasswordRequest $request)
    {
        return $this->service->changePassword($request);
    }

    public function getNotifications($userId)
    {
        return $this->service->getNotifications($userId);
    }

    public function getNotification($userId, $id)
    {
        return $this->service->getNotification($userId, $id);
    }

    public function updateNotification(NotificationRequest $request)
    {
        return $this->service->updateNotification($request);
    }

    public function deleteNotification($userId, $id)
    {
        return $this->service->deleteNotification($userId, $id);
    }

    public function markAllNotificationsAsRead($userId)
    {
        return $this->service->markAllNotificationsAsRead($userId);
    }

    public function saveFCMToken(SaveFCMTokenRequest $request)
    {
        return $this->service->saveFCMToken($request);
    }

    public function removeFCMToken(Request $request)
    {
        $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        return $this->service->removeFCMToken($request);
    }

    public function getAnnouncements()
    {
        return $this->service->getAnnouncements();
    }

    public function markAsRead(Request $request)
    {
        $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'announcement_id' => ['required', 'integer', 'exists:announcements,id'],
        ]);

        return $this->service->markAsRead($request);
    }

    public function deleteAccount(Request $request)
    {
        $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        return $this->service->deleteAccount($request);
    }
}
