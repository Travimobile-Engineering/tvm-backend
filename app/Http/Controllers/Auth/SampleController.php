<?php

namespace App\Http\Controllers\Auth;

use App\Trait\HttpResponse;
use App\Http\Controllers\Controller;
use App\Services\Auth\AuthService;

class SampleController extends Controller
{
    use HttpResponse;

    public function __construct(protected AuthService $service)
    {}


}

