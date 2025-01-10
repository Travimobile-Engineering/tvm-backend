<?php

namespace App\Http\Controllers;

use App\Services\OtherService;
use Illuminate\Http\Request;

class OtherController extends Controller
{
    protected $service;

    public function __construct(OtherService $service)
    {
        $this->service = $service;
    }

    public function getStates()
    {
        return $this->service->getStates();
    }

    public function getBank()
    {
        return $this->service->getBank();
    }

    public function accountLookUp(Request $request)
    {
        return $this->service->accountLookUp($request);
    }
}
