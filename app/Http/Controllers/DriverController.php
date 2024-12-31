<?php

namespace App\Http\Controllers;

use App\Http\Requests\DriverInfoRequest;
use App\Services\DriverService;
use Illuminate\Http\Request;

class DriverController extends Controller
{
    protected $service;

    public function __construct(DriverService $service)
    {
        $this->service = $service;
    }

    public function addDriverInfo(DriverInfoRequest $request)
    {
        return $this->service->addDriverInfo($request);
    }

    public function addBusStop(Request $request)
    {
        return $this->service->addBusStop($request);
    }

    public function getAllBusStops($userId)
    {
        return $this->service->getAllBusStops($userId);
    }

    public function getStop($userId, $stateId)
    {
        return $this->service->getStop($userId, $stateId);
    }
}
