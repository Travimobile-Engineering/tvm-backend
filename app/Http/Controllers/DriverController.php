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

    public function removeDocument($id)
    {
        return $this->service->removeDocument($id);
    }

    public function updateDriverDocuments(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id'
        ]);

        return $this->service->updateDriverDocuments($request);
    }

    public function updateUnion(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'transit_company_union_id' => 'required|exists:transit_company_unions,id',
        ]);

        return $this->service->updateUnion($request);
    }
}
