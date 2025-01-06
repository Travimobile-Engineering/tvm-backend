<?php

namespace App\Http\Controllers;

use App\Trait\HttpResponse;
use App\Models\TransitCompany;
use Illuminate\Http\JsonResponse;
use App\Services\TransitCompanyService;
use App\Http\Requests\TransitCompany\StoreRequest;
use App\Http\Requests\TransitCompany\UpdateRequest;

class TransitCompanyController extends Controller
{
    use HttpResponse;
    
    protected $service;

    public function __construct(TransitCompanyService $service){
        $this->service = $service;
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRequest $request): JsonResponse
    {
        $response = $this->service->store($request);
        return $this->success($response['data'], $response['message'], 200);
    }

    /**
     * Display the specified resource.
     */
    public function show(TransitCompany $transitCompany)
    {
        $response = $this->service->show($transitCompany);
        return $this->response($response);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRequest $request, TransitCompany $transitCompany)
    {
        $response = $this->service->update($request, $transitCompany);
        return $this->response($response);
    }

    public function getUnions(){
        return $this->service->getUnions();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TransitCompany $transitCompany)
    {
        //
    }
}
