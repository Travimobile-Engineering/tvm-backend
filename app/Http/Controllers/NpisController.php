<?php

namespace App\Http\Controllers;

use App\Services\NpisService;
use Illuminate\Http\Request;

class NpisController extends Controller
{
    public function __construct(
        protected NpisService $npisService
    ) {}

    public function createEvent(Request $request)
    {
        return $this->npisService->createEvent($request);
    }

    public function getEvents()
    {
        return $this->npisService->getEvents();
    }

    public function getEvent($id)
    {
        return $this->npisService->getEvent($id);
    }
}
