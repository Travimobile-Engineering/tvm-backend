<?php

namespace App\Http\Controllers;

use App\Trait\HttpResponse;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    use HttpResponse;
    public function all(){
        return $this->response(['data' => Notification::where('user_id', Auth::id())->orderBy('created_at', 'desc')->get()]);
    }
}
