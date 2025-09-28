<?php

namespace App\Services;

use App\Models\Notification;
use App\Trait\HttpResponse;
use Illuminate\Support\Facades\Auth;

class NotificationService
{
    use HttpResponse;

    public function all()
    {
        $notifications = Notification::where('user_id', Auth::id())
            ->latest()
            ->paginate(25);

        return $this->withPagination($notifications, 'All Notifications');
    }

    public function show($notification)
    {
        return $this->success($notification, 'Notification retrieved successfully');
    }

    public function delete($notification)
    {
        $notification->delete();

        return $this->success(null, 'Notification deleted successfully');
    }
}
