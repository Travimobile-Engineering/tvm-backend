<?php

namespace App\Services;

use App\Mail\NtemEventConfirmationMail;
use App\Models\NpisEvent;
use App\Models\NtemEvent;
use App\Trait\HttpResponse;
use Illuminate\Support\Facades\Mail;

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
        $ntemEvent = NtemEvent::create([
            'full_name' => $request->full_name,
            'email' => $request->email,
            'phone_number' => $request->phone_number,
            'organization' => $request->organization,
            'job_title' => $request->job_title,
            'state' => $request->state,
            'referral_source' => $request->referral_source,
            'dietary_preference' => $request->dietary_preference,
        ]);

        Mail::to($ntemEvent->email)->send(new NtemEventConfirmationMail($ntemEvent)); // we can remove this later I just added a temp mail to assist send confirmation email to attendees.

        return $this->success($ntemEvent, 'NTEM Event registration successful', 201);
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
