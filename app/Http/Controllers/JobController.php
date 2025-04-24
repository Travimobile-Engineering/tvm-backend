<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddJobRequest;
use App\Http\Requests\JobApplyRequest;
use App\Services\JobService;
use Illuminate\Http\Request;

class JobController extends Controller
{
    public function __construct(
        protected JobService $service,
    ){}
    public function getJobs(){
        return $this->service->getJobs();
    }
    
    public function getJob(Request $request){
        return $this->service->getJob($request);
    }
    
    public function apply(JobApplyRequest $request){
        return $this->service->apply($request);
    }

    public function addJob(AddJobRequest $request){
        return $this->service->addJob($request);
    }
}
