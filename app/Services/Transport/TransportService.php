<?php

namespace App\Services\Transport;

use App\Enum\TransportStatus;
use App\Enum\TransportType;
use App\Models\Transport;
use App\Trait\HttpResponse;

class TransportService
{
    use HttpResponse;

    public function createOneTime($request)
    {
        try {

            $transport = Transport::create([
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

            return $this->success($transport, "Created successfully", 201);
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}

