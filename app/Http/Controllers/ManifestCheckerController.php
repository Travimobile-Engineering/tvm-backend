<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddIncidentRequest;
use App\Services\ManifestCheckerService;
use App\Trait\HttpResponse;
use Illuminate\Http\Request;

class ManifestCheckerController extends Controller
{
    protected $service;

    public function __construct(){
        $this->service = new ManifestCheckerService();
    }
    use HttpResponse;
    public function getManifestData(Request $request){
        return $this->service->getManifestData($request);
    }

    public function addIncident(AddIncidentRequest $request){
        return $this->service->addIncident($request);
    }

    public function getIncidentCategories(){
        return $this->service->getIncidentCategories();
    }

    public function getIncidentTypes(){
        return $this->service->getIncidentTypes();
    }

    public function getIncidentSeverityLevels(){
        return $this->service->getIncidentSeverityLevels();
    }
}
