<?php

namespace App\Http\Controllers;

use App\Http\Requests\TransitCompany\StoreRequest;
use App\Http\Requests\TransitCompany\UpdateRequest;
use App\Models\TransitCompany;
use App\Services\TransitCompanyService;
use App\Trait\HttpResponse;
use Illuminate\Http\JsonResponse;

class TransitCompanyController extends Controller
{
    use HttpResponse;

    public function __construct(
        protected TransitCompanyService $service
    ) {}

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
        return $this->service->store($request);
    }

    /**
     * Display the specified resource.
     */
    public function show(TransitCompany $transitCompany)
    {
        return $this->service->show($transitCompany);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRequest $request, TransitCompany $transitCompany)
    {
        return $this->service->update($request, $transitCompany);
    }

    public function getUnions()
    {
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
