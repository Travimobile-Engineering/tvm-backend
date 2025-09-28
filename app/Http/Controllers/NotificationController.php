<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Services\NotificationService;
use App\Trait\HttpResponse;

class NotificationController extends Controller
{
    use HttpResponse;

    public function __construct(
        protected NotificationService $notificationService
    ) {}

    public function all()
    {
        return $this->notificationService->all();
    }

    public function show(Notification $notification)
    {
        return $this->notificationService->show($notification);
    }

    public function delete(Notification $notification)
    {
        return $this->notificationService->delete($notification);
    }
}
