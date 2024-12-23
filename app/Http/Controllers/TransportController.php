<?php

namespace App\Http\Controllers;

use App\Http\Requests\TransportOneTimeRequest;
use App\Services\Transport\TransportService;
use Illuminate\Http\Request;

class TransportController extends Controller
{
    protected $service;

    public function __construct(TransportService $service)
    {
        $this->service = $service;
    }

    public function createOneTime(TransportOneTimeRequest $request)
    {
        return $this->service->createOneTime($request);
    }
}
