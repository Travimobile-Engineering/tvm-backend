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
        $request->validate([
            'rank' => 'required|string|max:255',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone_number' => 'required|string|max:20',
            'email' => 'required|email|max:255',
        ]);

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
