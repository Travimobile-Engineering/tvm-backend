<?php

namespace App\Services\Trip;

use App\Models\Trip;
use App\Enum\TripType;
use App\Enum\TripStatus;
use App\Models\TripBooking;
use App\Trait\HttpResponse;
use App\Models\TransitCompany;
use App\Models\Vehicle\Vehicle;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\TripResource;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\OneTimeTripResource;
use App\Http\Resources\RecurringTripResource;

use App\Models\Manifest;

class TripService
{
    use HttpResponse;

    public function createOneTime($request)
    {
        $user = Auth::user();

        $tCompany = TransitCompany::with('user')
            ->where('id', $request->transit_company_id)
            ->first();

        if(! $tCompany) {
            return $this->error(null, "Invalid company ID", 400);
        }

        if($tCompany->user->id != $user->id) {
            return $this->error(null, "You do not have permission to complete this request", 400);
        }

        $vehicle = Vehicle::where('id', $request->vehicle_id)->first();

        if (! $vehicle) {
            return $this->error(null, "Invalid vehicle ID");
        }

        if($vehicle->company_id != $tCompany->id) {
            return $this->error(null, "You do not have permission to complete this request");
        }

        $departure = DB::table('covered_routes')
            ->where('id', $request->departure_id)
            ->select('from_subregion_id', 'to_subregion_id')
            ->first();

        $destination = DB::table('covered_routes')
            ->where('id', $request->destination_id)
            ->select('from_subregion_id', 'to_subregion_id')
            ->first();

        try {

            $trip = Trip::create([
                'user_id' => $request->user_id,
                'vehicle_id' => $request->vehicle_id,
                'transit_company_id' => $request->transit_company_id,
                'departure' => $departure->from_subregion_id,
                'destination' => $destination->to_subregion_id,
                'departure_date' => $request->departure_date,
                'departure_time' => $request->departure_time,
                'repeat_trip' => $request->repeat_trip,
                'bus_type' => $request->bus_type,
                'price' => $request->price,
                'bus_stops' => $request->bus_stops ?? [],
                'means' => $request->means ?? 1,
                'type' => TripType::ONETIME,
                'status' => TripStatus::ACTIVE,
            ]);

            return $this->success($trip, "Created successfully", 201);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function getTrip(Trip $trip){

        if(!$trip) {
            return $this->error(null, "not found", 404);
        }

        $seats = Vehicle::where('id', $trip['vehicle_id'])->pluck('seats')->first();
        $seats = json_decode($seats);
        // $trip['available_seats'];

        $bookings = TripBooking::where('trip_id', $trip->trip_id)->where('status', 1);
        $trip['selected_seats'] = $bookings->pluck('selected_seat')->toArray();
        $trip['available_seats'] = array_values(array_filter($seats, function($seat) use ($trip){
            return !in_array($seat, $trip['selected_seats']);
        }));

        $trip['transit_company'] = TransitCompany::where('id', $trip->transit_company_id)
            ->select('name', 'reg_no')
            ->first();

        $trip['vehicle'] = Vehicle::where('id', $trip->vehicle_id)
            ->select('name', 'ac', 'plate_no')
            ->first();

        $trip['vehicle']['seats'] = $seats;

        $seat_columns = [];
        $seat_rows = 0;

        foreach($seats as $seat){
            $seat_parts = str_split($seat);
            $seat_alph = $seat_parts[0];
            $seat_num = $seat_parts[1];

            if(!in_array($seat_alph, $seat_columns)) {
                $seat_columns[] = $seat_alph;
            }

            if($seat_num > $seat_rows) {
                $seat_rows = $seat_num;
            }
        }

        $trip['vehicle']['seat_rows'] = $seat_rows;
        $trip['vehicle']['seat_columns'] = count($seat_columns);

        return $this->success($trip, "Trips");

    }

    public function getTrips($request)
    {
        $trips = Trip::where('trips.status', 1);

        if (!empty($request->date) || !empty($request->time)) {
            $date = $request->date ?? date('Y-m-d');
            $time = $request->time ?? '00:00:00';
            $departureAt = "$date $time";
            $trips->where('departure_at', '>=', $departureAt);
        }

        if (!empty($request->departure)) {
            $trips->where('departure', $request->departure);
        }

        if (!empty($request->destination)) {
            $trips->where('destination', $request->destination);
        }

        $trips->join('route_subregions as from_subregion', 'trips.departure', '=', 'from_subregion.id')
            ->join('route_subregions as to_subregion', 'trips.destination', '=', 'to_subregion.id')
            ->join('vehicles', 'trips.vehicle_id', '=', 'vehicles.id')
            ->select(
                'trips.*',
                'vehicles.name as vehicle_name',
                'vehicles.ac as vehicle_has_ac',
                'vehicles.plate_no as vehicle_plate_no',
                'vehicles.color as vehicle_color',
                'from_subregion.name as departure_town',
                'to_subregion.name as destination_town'
            );

        return $this->success($trips->get(), "Trips");
    }

    public function getOneTime($id)
    {
        $transport = Trip::with(['user', 'tripBookings.user'])
            ->where('type', TripType::ONETIME)
            ->find($id);

        if (!$transport) {
            return $this->error("Transport not found", 404);
        }

        $data = new OneTimeTripResource($transport);

        return $this->success($data, "Transport found", 200);
    }

    public function getUserOneTimes($userId)
    {
        $trips = Trip::with(['user', 'tripBookings.user'])
            ->where('user_id', $userId)
            ->where('type', TripType::ONETIME)
            ->get();

        $data = OneTimeTripResource::collection($trips);

        return $this->success($data, "Transport found", 200);
    }

    public function editOneTime($request, $id)
    {
        $transport = Trip::where('type', TripType::ONETIME)
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

        $user = Auth::user();

        $tCompany = TransitCompany::with('user')
            ->where('id', $request->transit_company_id)
            ->first();

        if(! $tCompany) {
            return $this->error(null, "Invalid company ID", 400);
        }

        if($tCompany->user->id != $user->id) {
            return $this->error(null, "You do not have permission to complete this request", 400);
        }

        $vehicle = Vehicle::where('id', $request->vehicle_id)->first();

        if (! $vehicle) {
            return $this->error(null, "Invalid vehicle ID");
        }

        if($vehicle->company_id != $tCompany->id) {
            return $this->error(null, "You do not have permission to complete this request");
        }

        $departure = DB::table('covered_routes')
        ->where('id', $request->departure_id)
        ->select('from_subregion_id', 'to_subregion_id')
        ->first();

        $destination = DB::table('covered_routes')
            ->where('id', $request->destination_id)
            ->select('from_subregion_id', 'to_subregion_id')
            ->first();

        try {

            Trip::create([
                'user_id' => $request->user_id,
                'vehicle_id' => $request->vehicle_id,
                'transit_company_id' => $request->transit_company_id,
                'departure' => $departure->from_subregion_id,
                'destination' => $destination->to_subregion_id,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'trip_days' => $request->trip_days,
                'reoccur_duration' => $request->reoccur_duration,
                'bus_type' => $request->bus_type,
                'price' => $request->price,
                'bus_stops' => $request->bus_stops ?? [],
                'means' => $request->means ?? 1,
                'type' => TripType::RECURRING,
                'status' => TripStatus::ACTIVE,
            ]);

            return $this->success(null, "Created successfully", 201);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function getRecurring($id)
    {
        $transport = Trip::with(['user', 'tripBookings.user'])
            ->where('type', TripType::RECURRING)
            ->find($id);

        if (!$transport) {
            return $this->error("Transport not found", 404);
        }

        $data = new RecurringTripResource($transport);

        return $this->success($data, "Transport found", 200);
    }

    public function getUserRecurrings($userId)
    {
        $trips = Trip::with(['user', 'tripBookings.user'])
            ->where('user_id', $userId)
            ->where('type', TripType::RECURRING)
            ->get();

        $data = RecurringTripResource::collection($trips);

        return $this->success($data, "Transport found", 200);
    }

    public function editRecurring($request, $id)
    {
        $transport = Trip::where('type', TripType::RECURRING)
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
        $transport = Trip::find($id);

        if (! $transport) {
            return $this->error(null, "Data not found!", 404);
        }

        $transport->update([
            'reason' => $request->reason,
            'date_cancelled' => now(),
            'status' => TripStatus::CANCELLED,
        ]);

        return $this->success(null, "Trip Cancelled Successfully", 200);
    }

    public function completeTrip($id)
    {
        $transport = Trip::find($id);

        if (! $transport) {
            return $this->error(null, "Data not found!", 404);
        }

        $transport->update([
            'status' => TripStatus::COMPLETED,
        ]);

        return $this->success(null, "Trip Completed Successfully", 200);
    }

    public function getUpcomingTrips($userId)
    {
        $date = request()->query('date');

        $query = Trip::with(['user', 'tripBookings.user'])
            ->where('user_id', $userId)
            ->where('status', TripStatus::INPROGRESS);

        if ($date) {
            $query->whereDate('created_at', '=', $date);
        }

        $trips = $query->get();

        $data = RecurringTripResource::collection($trips);

        return $this->success($data, "Upcoming trips", 200);
    }


    public function getCompletedTrips($userId)
    {
        $trips = Trip::with(['user', 'tripBookings.user'])
            ->where('user_id', $userId)
            ->where('status', TripStatus::COMPLETED)
            ->get();

        $data = RecurringTripResource::collection($trips);

        return $this->success($data, "Completed trips", 200);
    }

    public function getCancelledTrips($userId)
    {
        $trips = Trip::with(['user', 'tripBookings.user'])
            ->where('user_id', $userId)
            ->where('status', TripStatus::CANCELLED)
            ->get();

        $data = RecurringTripResource::collection($trips);

        return $this->success($data, "Completed trips", 200);
    }

    public function getAllTrips($userId)
    {
        $type = request()->query('type');
        $date = request()->query('date');

        $query = Trip::with(['user', 'tripBookings.user'])
            ->where('user_id', $userId)
            ->where('status', TripStatus::ACTIVE);

        if ($type) {
            $query->where('type', $type);
        }

        if ($date) {
            $query->whereDate('created_at', $date);
        }

        $trips = $query->get();

        if ($type === TripType::RECURRING) {
            $data = RecurringTripResource::collection($trips);
        } elseif($type === TripType::ONETIME) {
            $data = OneTimeTripResource::collection($trips);
        } else {
            $data = TripResource::collection($trips);
        }

        return $this->success($data, "All trips", 200);
    }

    public function getAll()
    {
        $type = request()->query('type');
        $date = request()->query('date');

        $query = Trip::with(['user', 'tripBookings.user'])
            ->where('status', TripStatus::ACTIVE);

        if ($type) {
            $query->where('type', $type);
        }

        if ($date) {
            $query->whereDate('created_at', $date);
        }

        $trips = $query->get();

        if ($type === TripType::RECURRING) {
            $data = RecurringTripResource::collection($trips);
        } else {
            $data = OneTimeTripResource::collection($trips);
        }

        return $this->success($data, "All trips", 200);
    }

    public function getManifestInfo($tripId, $userId)
    {
        $transport = Trip::with(['user', 'tripBookings.user'])
            ->where('id', $tripId)
            ->first();

        if (! $transport) {
            return $this->error("Transport not found", 404);
        }

        $passenger = $transport->tripBookings
            ->where('user_id', $userId)
            ->first();

        if (! $passenger) {
            return $this->error("You do not have permission to complete this request", 400);
        }

        $info = [
            'ticket_id' => $passenger->booking_id,
            'first_name' => $passenger->user->first_name,
            'last_name' => $passenger->user->last_name,
            'email' => $passenger->user->email,
            'phone_number' => $passenger->user->phone_number,
            'next_of_kin' => $passenger->next_of_kin_fullname,
            'next_of_kin_phone' => $passenger->next_of_kin_phone_number,
            'departure' => $transport->departure,
            'destination' => $transport->destination,
            'seat' => (int)$passenger->selected_seat,
        ];

        return $this->success($info, "Passenger Manifest Detail", 200);
    }

    public function startTrip($request)
    {
        $trip = Trip::with(['tripBookings.user', 'manifests'])->find($request->trip_id);

        if (! $trip) {
            return $this->error(null, "Data not found!", 404);
        }

        DB::transaction(function () use ($trip) {

            if (! empty($trip->tripBookings)) {
                foreach ($trip->tripBookings as $booking) {
                    $book = new Manifest([
                        'trip_id' => $trip->id,
                        'booking_id' => $booking->booking_id,
                        'first_name' => $booking->user->first_name,
                        'last_name' => $booking->user->last_name,
                        'email' => $booking->user->email,
                        'phone_number' => $booking->user->phone_number,
                        'next_of_kin' => $booking->next_of_kin_fullname,
                        'next_of_kin_phone' => $booking->next_of_kin_phone_number,
                        'seat' => $booking->selected_seat,
                    ]);

                    $trip->manifests()->save($book);
                }
            }

            $trip->update([
                'status' => TripStatus::INPROGRESS,
            ]);
        });

        return $this->success(null, "Trip Started Successfully", 200);
    }
}

