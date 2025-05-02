<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddIncidentRequest;
use App\Http\Requests\WatchListRequest;
use App\Models\WatchList;
use App\Services\ManifestCheckerService;
use App\Trait\HttpResponse;
use Illuminate\Http\Request;

class ManifestCheckerController extends Controller
{
    use HttpResponse;

    public function __construct(
        protected ManifestCheckerService $service
    )
    {}

    public function getManifestData(Request $request)
    {
        return $this->service->getManifestData($request);
    }

    public function addIncident(AddIncidentRequest $request)
    {
        return $this->service->addIncident($request);
    }

    public function getIncidentCategories()
    {
        return $this->service->getIncidentCategories();
    }

    public function getIncidentTypes()
    {
        return $this->service->getIncidentTypes();
    }

    public function getIncidentSeverityLevels()
    {
        return $this->service->getIncidentSeverityLevels();
    }

    public function getIncidents(){
        return $this->service->getIncidents();
    }

    public function getIncident(Request $request){
        return $this->service->getIncident($request);
    }

    public function addRecordToWatchList(WatchListRequest $request){
        return $this->service->addUpdateWatchList($request);
    }

    public function updateWatchListRecord(Request $request){
        return $this->service->addUpdateWatchList($request, 'update');
    }

    public function getWatchListRecords(){
        return $this->service->getWatchListRecords();
    }

    public function getWatchListRecord(Request $request){
        return $this->service->getWatchListRecord($request);
    }

    public function searchWatchList(Request $request){
        $request->validate(['name' => 'string, required']);
        return $this->service->searchWatchList($request);
    }

    public function getProfile(){
        return $this->service->getProfile();
    }

    public function editProfile(Request $request){
        return $this->service->editProfile($request);
    }
}
