<?php

namespace App\Http\Controllers;

use App\Http\Requests\DriverInfoRequest;
use App\Http\Requests\SetAvailabilityRequest;
use App\Http\Requests\UpdateSeatLayoutRequest;
use App\Http\Requests\VehicleRequirementRequest;
use App\Services\AgentService;
use App\Services\DriverService;
use Illuminate\Http\Request;

class DriverController extends Controller
{
    public function __construct(
        protected DriverService $service,
        protected AgentService $agentService
    ) {}

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
            'user_id' => 'required|exists:users,id',
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

    public function setupVehicle(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        return $this->service->setupVehicle($request);
    }

    public function premiumUpgrade(VehicleRequirementRequest $request)
    {
        return $this->service->premiumUpgrade($request);
    }

    public function editDescription(Request $request)
    {
        return $this->service->editDescription($request);
    }

    public function setAvailability(SetAvailabilityRequest $request)
    {
        return $this->service->setAvailability($request);
    }

    public function scanTicket(Request $request, $bookingId = null, $passengerId = null)
    {
        return $this->agentService->scanTicket($request, $bookingId, $passengerId);
    }

    public function updateLayout(UpdateSeatLayoutRequest $request)
    {
        return $this->service->updateLayout($request);
    }
}
