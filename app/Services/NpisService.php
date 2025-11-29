<?php

namespace App\Services;

use App\Models\NpisEvent;
use App\Trait\HttpResponse;

class NpisService
{
    use HttpResponse;

    public function createEvent($request)
    {
        $npisEvent = NpisEvent::create([
            'rank' => $request->rank,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'phone_number' => $request->phone_number,
            'email' => $request->email,
        ]);

        return $this->success($npisEvent, 'NPI Event created successfully', 201);
    }

    public function getEvents()
    {
        $npisEvents = NpisEvent::select('id', 'rank', 'first_name', 'last_name', 'phone_number', 'email')
            ->get();

        return $this->success($npisEvents, 'NPI Events retrieved successfully');
    }

    public function getEvent($id)
    {
        $npisEvent = NpisEvent::select('id', 'rank', 'first_name', 'last_name', 'phone_number', 'email')
            ->find($id);

        if (! $npisEvent) {
            return $this->error(null, 'NPI Event not found', 404);
        }

        return $this->success($npisEvent, 'NPI Event retrieved successfully');
    }
}
