<?php

namespace App\Http\Controllers;

use App\Http\Requests\NotificationRequest;
use App\Services\UserService;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(
        protected UserService $service,
    )
    {}

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:8'],
            'confirm_password' => ['required', 'same:new_password']
        ]);

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
}
