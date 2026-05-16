<?php

namespace App\Services;

use App\Models\NpisEvent;
use App\Models\NtemEvent;
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

    public function createNtemEvent($request)
    {
        $ntemEvent = NtemEvent::create($request->all());

        return $this->success($ntemEvent, 'NTM Event created successfully', 201);
    }

    public function getNtemEvents()
    {
        $ntemEvents = NtemEvent::select('id', 'full_name', 'email', 'phone_number', 'organization', 'job_title', 'state', 'referral_source', 'dietary_preference')
            ->get();

        return $this->success($ntemEvents, 'NTM Events retrieved successfully');
    }

    public function getNtemEvent($id)
    {
        $ntemEvent = NtemEvent::select('id', 'full_name', 'email', 'phone_number', 'organization', 'job_title', 'state', 'referral_source', 'dietary_preference')
            ->find($id);

        if (! $ntemEvent) {
            return $this->error(null, 'NTM Event not found', 404);
        }

        return $this->success($ntemEvent, 'NTM Event retrieved successfully');
    }
}
