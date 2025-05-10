<?php

namespace App\Services;

use App\Models\Announcement;
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

    public function removeFCMToken($request)
    {
        $user = User::find($request->user_id);

        if (! $user) {
            return $this->error(null, 'User not found', 404);
        }

        $user->update([
            'fcm_token' => null,
        ]);

        return $this->success(null, 'FCM token removed successfully');
    }

    public function getAnnouncements()
    {
        $auth = authUser();
        $user = User::with('announcements')->findOrFail($auth->id);

        $announcements = Announcement::select('id', 'title', 'description', 'priority', 'image')
            ->orderBy('priority', 'desc')
            ->get();

        $announcements->each(function ($announcement) use ($user) {
            $pivot = $user->announcements()->where('announcement_id', $announcement->id)->first();

            if ($pivot) {
                $announcement->read_status = $pivot->pivot->status;
            } else {
                $announcement->read_status = 'unread';
            }
        });

        return $this->success($announcements, 'Announcements retrieved successfully');
    }

    public function markAsRead($request)
    {
        $user = User::with('announcements')->find($request->user_id);

        if (! $user) {
            return $this->error(null, 'User not found', 404);
        }

        $existingPivot = $user->announcements()->where('announcement_id', $request->announcement_id)->exists();

        if ($existingPivot) {
            $user->announcements()->updateExistingPivot(
                $request->announcement_id,
                [
                    'status' => 'read',
                    'updated_at' => now(),
                ]
            );
        } else {
            $user->announcements()->attach(
                $request->announcement_id,
                [
                    'status' => 'read',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        return $this->success(null, 'Announcement marked as read');
    }
}


