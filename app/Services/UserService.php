<?php

namespace App\Services;

use App\Models\User;
use App\Trait\HttpResponse;
use Illuminate\Support\Facades\Hash;

class UserService
{
    use HttpResponse;

    public function __construct(
        protected AgentService $agentService
    )
    {}

    public function changePassword($request)
    {
        $user = User::find($request->user_id);

        if (! $user) {
            return $this->error(null, 'User not found', 404);
        }

        if (Hash::check($request->current_password, $user->password)) {
            $user->update([
                'password' => Hash::make($request->new_password),
            ]);

             return $this->success(null, "Password changed successfully");

        }else {
            return $this->error(null, 'Old Password did not match', 422);
        }
    }

    public function getNotifications($userId)
    {
        $user = User::with('userNotifications:id,user_id,title,description,additional_data,read,created_at')
            ->find($userId);

        if (! $user) {
            return $this->error(null, 'User not found', 404);
        }

        $data = $user->userNotifications;

        return $this->success($data, 'Notifications retrieved successfully');
    }

    public function getNotification($userId, $id)
    {
        $user = User::with('userNotifications')
            ->find($userId);

        if (! $user) {
            return $this->error(null, 'User not found', 404);
        }

        $data = $user->userNotifications()
            ->select('id', 'user_id', 'title', 'description', 'additional_data', 'read', 'created_at')
            ->where('id', $id)
            ->first();

        if (! $data) {
            return $this->error(null, 'Notification not found', 404);
        }

        return $this->success($data, 'Notification retrieved successfully');
    }

    public function updateNotification($request)
    {
        return $this->agentService->updateNotification($request);
    }

    public function saveFCMToken($request)
    {
        $user = User::find($request->user_id);

        if (! $user) {
            return $this->error(null, 'User not found', 404);
        }

        $user->update([
            'fcm_token' => $request->fcm_token,
        ]);

        return $this->success(null, 'FCM token saved successfully');
    }
}


