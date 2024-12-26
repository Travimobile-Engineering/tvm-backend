<?php

namespace App\Services\Transport;

use App\Enum\TransportStatus;
use App\Enum\TransportType;
use App\Http\Resources\OneTimeTripResource;
use App\Http\Resources\RecurringTripResource;
use App\Models\Transport;
use App\Trait\HttpResponse;

class TransportService
{
    use HttpResponse;

    public function createOneTime($request)
    {
        try {

            Transport::create([
                'user_id' => $request->user_id,
                'departure' => $request->departure,
                'destination' => $request->destination,
                'departure_date' => $request->departure_date,
                'departure_time' => $request->departure_time,
                'repeat_trip' => $request->repeat_trip,
                'bus_type' => $request->bus_type,
                'ticket_price' => $request->ticket_price,
                'bus_stops' => $request->bus_stops,
                'type' => TransportType::ONETIME,
                'status' => TransportStatus::ACTIVE,
            ]);

            return $this->success(null, "Created successfully", 201);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function getOneTime($id)
    {
        $transport = Transport::with(['user'])
            ->where('type', TransportType::ONETIME)
            ->find($id);

        if (!$transport) {
            return $this->error("Transport not found", 404);
        }

        $data = new OneTimeTripResource($transport);

        return $this->success($data, "Transport found", 200);
    }

    public function getUserOneTimes($userId)
    {
        $transports = Transport::with('user')
            ->where('user_id', $userId)
            ->where('type', TransportType::ONETIME)
            ->get();

        $data = OneTimeTripResource::collection($transports);

        return $this->success($data, "Transport found", 200);
    }

    public function editOneTime($request, $id)
    {
        $transport = Transport::where('type', TransportType::ONETIME)
            ->find($id);

        if (! $transport) {
            return $this->error(null, "Data not found!", 404);
        }

        $transport->update([
            'departure' => $request->departure,
            'destination' => $request->destination,
            'departure_date' => $request->departure_date,
            'departure_time' => $request->departure_time,
            'repeat_trip' => $request->repeat_trip,
            'bus_type' => $request->bus_type,
            'ticket_price' => $request->ticket_price,
            'bus_stops' => $request->bus_stops,
        ]);

        return $this->success(null, "Updated Successfully", 200);
    }

    public function createRecurring($request)
    {
        try {

            Transport::create([
                'user_id' => $request->user_id,
                'departure' => $request->departure,
                'destination' => $request->destination,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'trip_days' => $request->trip_days,
                'reoccur_duration' => $request->reoccur_duration,
                'bus_type' => $request->bus_type,
                'ticket_price' => $request->ticket_price,
                'bus_stops' => $request->bus_stops,
                'type' => TransportType::RECURRING,
                'status' => TransportStatus::ACTIVE,
            ]);

            return $this->success(null, "Created successfully", 201);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function getRecurring($id)
    {
        $transport = Transport::with('user')
            ->where('type', TransportType::RECURRING)
            ->find($id);

        if (!$transport) {
            return $this->error("Transport not found", 404);
        }

        $data = new RecurringTripResource($transport);

        return $this->success($data, "Transport found", 200);
    }

    public function getUserRecurrings($userId)
    {
        $transports = Transport::with('user')
            ->where('user_id', $userId)
            ->where('type', TransportType::RECURRING)
            ->get();

        $data = RecurringTripResource::collection($transports);

        return $this->success($data, "Transport found", 200);
    }

    public function editRecurring($request, $id)
    {
        $transport = Transport::where('type', TransportType::RECURRING)
            ->find($id);

        if (! $transport) {
            return $this->error(null, "Data not found!", 404);
        }

        $transport->update([
            'departure' => $request->departure,
            'destination' => $request->destination,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'trip_days' => $request->trip_days,
            'reoccur_duration' => $request->reoccur_duration,
            'bus_type' => $request->bus_type,
            'ticket_price' => $request->ticket_price,
            'bus_stops' => $request->bus_stops,
        ]);

        return $this->success(null, "Updated Successfully", 200);
    }

    public function cancelTrip($request, $id)
    {
        $transport = Transport::find($id);

        if (! $transport) {
            return $this->error(null, "Data not found!", 404);
        }

        $transport->update([
            'reason' => $request->reason,
            'date_cancelled' => now(),
            'status' => TransportStatus::CANCELLED,
        ]);

        return $this->success(null, "Trip Cancelled Successfully", 200);
    }

    public function completeTrip($id)
    {
        $transport = Transport::find($id);

        if (! $transport) {
            return $this->error(null, "Data not found!", 404);
        }

        $transport->update([
            'status' => TransportStatus::COMPLETED,
        ]);

        return $this->success(null, "Trip Completed Successfully", 200);
    }

    public function getUpcomingTrips($userId)
    {
        $transports = Transport::with('user')
            ->where('user_id', $userId)
            ->where('status', TransportStatus::INPROGRESS)
            ->get();

        $data = RecurringTripResource::collection($transports);

        return $this->success($data, "Upcoming trips", 200);
    }

    public function getCompletedTrips($userId)
    {
        $transports = Transport::with('user')
            ->where('user_id', $userId)
            ->where('status', TransportStatus::COMPLETED)
            ->get();

        $data = RecurringTripResource::collection($transports);

        return $this->success($data, "Completed trips", 200);
    }

    public function getCancelledTrips($userId)
    {
        $transports = Transport::with('user')
            ->where('user_id', $userId)
            ->where('status', TransportStatus::CANCELLED)
            ->get();

        $data = RecurringTripResource::collection($transports);

        return $this->success($data, "Completed trips", 200);
    }
}

