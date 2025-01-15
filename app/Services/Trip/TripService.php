<?php

namespace App\Services\Trip;

use App\Models\Trip;
use App\Enum\TripType;
use App\Enum\TripStatus;
use App\Trait\HttpResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\TripResource;
use App\Http\Resources\OneTimeTripResource;
use App\Http\Resources\RecurringTripResource;
use App\Models\BusStop;
use App\Models\Manifest;
use App\Models\TripLog;
use App\Models\User;
use App\Trait\DriverTrait;
use Carbon\Carbon;

class TripService
{
    use HttpResponse, DriverTrait;

    const TRIP_CHARGE_AMOUNT = 1000;

    public function createOneTime($request)
    {
        try {

            $user = User::with(['transitCompany', 'vehicle'])->findOrFail($request->user_id);

            $trip = Trip::create([
                'user_id' => $user->id,
                'vehicle_id' => $request->vehicle_id ?? $user->vehicle->id,
                'transit_company_id' => $user->transitCompany?->id ?? 1,
                'departure' => $request->departure_id,
                'destination' => $request->destination_id,
                'trip_duration' => $request->trip_duration,
                'departure_date' => $request->departure_date,
                'departure_time' => $request->departure_time,
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

    public function getTrip(Trip $trip)
    {
        $trip->load(['user', 'tripBookings.user', 'departureRegion.state', 'destinationRegion.state', 'manifests', 'vehicle']);

        $data = new TripResource($trip);

        return $this->success($data, "Trip details");
    }

    // Old Code New code being used (getTrip)
    public function getTripss($request)
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

    /**
     * Refactored to improve code readability, maintainability, and performance:
     * - Utilizes eager loading (`with`) to optimize database queries by reducing the number of queries.
     * - Replaces multiple conditional `where` calls with a more compact and dynamic closure for query construction.
     * - Filters data using query parameters (`request()->query`) for flexibility and adherence to RESTful practices.
     * - Applies a resource transformation (`TripResource`) for a consistent API response format.
     * - Ensures that queries are dynamically constructed based on input parameters, promoting reusability and clean code.
     */
    public function getTrips($request)
    {
        $departure = request()->query('departure');
        $destination = request()->query('destination');

        $query = Trip::with(
                [
                    'user',
                    'vehicle',
                    'departureRegion.state',
                    'destinationRegion.state',
                    'manifests'
                ]
            )
            ->where('status', TripStatus::ACTIVE)
            ->where(function ($query) use ($request) {
                $date = $request->query('date', date('Y-m-d'));
                $time = $request->query('time', '00:00:00');
                $departureAt = "$date $time";

                $query->where('departure_at', '>=', $departureAt)
                    ->orWhere('start_date', '>=', now());
            });

        if ($departure) {
            $query->where('departure', $departure);
        }

        if ($destination) {
            $query->where('destination', $destination);
        }

        $trips = $query->get();

        $data = TripResource::collection($trips);

        return $this->success($data, "Available trips", 200);
    }

    public function getOneTime($id)
    {
        $trip = Trip::with(
                [
                    'user',
                    'vehicle',
                    'tripBookings.user',
                    'departureRegion.state',
                    'destinationRegion.state',
                    'manifests'
                ]
            )
            ->where('type', TripType::ONETIME)
            ->find($id);

        if (!$trip) {
            return $this->error("Trip not found", 404);
        }

        $data = new OneTimeTripResource($trip);

        return $this->success($data, "Trip found", 200);
    }

    public function getUserOneTimes($userId)
    {
        $trips = Trip::with(
                [
                    'user',
                    'vehicle',
                    'tripBookings.user',
                    'departureRegion.state',
                    'destinationRegion.state',
                    'manifests'
                ]
            )
            ->where('user_id', $userId)
            ->where('type', TripType::ONETIME)
            ->get();

        $data = OneTimeTripResource::collection($trips);

        return $this->success($data, "Trip found", 200);
    }

    public function editOneTime($request, $id)
    {
        $trip = Trip::where('type', TripType::ONETIME)
            ->find($id);

        if (! $trip) {
            return $this->error(null, "Data not found!", 404);
        }

        $trip->update([
            'departure' => $request->departure_id,
            'destination' => $request->destination_id,
            'departure_date' => $request->departure_date,
            'departure_time' => $request->departure_time,
            'bus_type' => $request->bus_type,
            'ticket_price' => $request->ticket_price,
            'bus_stops' => $request->bus_stops,
        ]);

        return $this->success(null, "Updated Successfully", 200);
    }

    public function createRecurring($request)
    {
        try {

            $user = User::with(['transitCompany', 'vehicle'])->findOrFail($request->user_id);

            $startDate = Carbon::parse($request->start_date);
            $endDate = $startDate->copy()->addMonths($request->reoccur_duration);
            $tripSchedule = $request->trip_days;

            foreach ($request->trip_days as $tripDay) {
                $day = strtolower($tripDay['day']);
                $time = $tripDay['time'];

                $currentDate = $startDate->copy();

                if (strtolower($currentDate->format('D')) !== $day) {
                    $currentDate = $currentDate->next($day);
                }

                while ($currentDate <= $endDate) {
                    $tripDateTime = $currentDate->copy()->setTimeFromTimeString($time);

                    Trip::create([
                        'user_id' => $user->id,
                        'vehicle_id' => $request->vehicle_id ?? $user->vehicle->id,
                        'transit_company_id' => $user->transitCompany?->id ?? 1,
                        'departure' => $request->departure_id,
                        'destination' => $request->destination_id,
                        'trip_duration' => $request->trip_duration,
                        'start_date' => $tripDateTime->format('Y-m-d'),
                        'trip_days' => [$day],
                        'trip_schedule' => $tripSchedule,
                        'reoccur_duration' => $request->reoccur_duration,
                        'bus_type' => $request->bus_type,
                        'price' => $request->price,
                        'bus_stops' => $request->bus_stops ?? [],
                        'means' => $request->means ?? 1,
                        'type' => TripType::RECURRING,
                        'status' => TripStatus::ACTIVE,
                    ]);

                    $currentDate->addWeek();
                }
            }

            return $this->success(null, "Recurring trips created successfully", 201);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function getRecurring($id)
    {
        $trip = Trip::with(
                [
                    'user',
                    'vehicle',
                    'tripBookings.user',
                    'departureRegion.state',
                    'destinationRegion.state',
                    'manifests'
                ]
            )
            ->where('type', TripType::RECURRING)
            ->find($id);

        if (!$trip) {
            return $this->error("Trip not found", 404);
        }

        $data = new RecurringTripResource($trip);

        return $this->success($data, "Trip found", 200);
    }

    public function getUserRecurrings($userId)
    {
        $trips = Trip::with(
                [
                    'user',
                    'vehicle',
                    'tripBookings.user',
                    'departureRegion.state',
                    'destinationRegion.state',
                    'manifests'
                ]
            )
            ->where('user_id', $userId)
            ->where('type', TripType::RECURRING)
            ->get();

        $data = RecurringTripResource::collection($trips);

        return $this->success($data, "Trip found", 200);
    }

    public function editRecurring($request, $id)
    {
        $trip = Trip::where('type', TripType::RECURRING)
            ->find($id);

        if (! $trip) {
            return $this->error(null, "Data not found!", 404);
        }

        $trip->update([
            'departure' => $request->departure_id,
            'destination' => $request->destination_id,
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
        $trip = Trip::find($id);

        if (! $trip) {
            return $this->error(null, "Data not found!", 404);
        }

        $trip->update([
            'reason' => $request->reason,
            'date_cancelled' => now(),
            'status' => TripStatus::CANCELLED,
        ]);

        return $this->success(null, "Trip Cancelled Successfully", 200);
    }

    public function completeTrip($id)
    {
        $trip = Trip::find($id);

        if (! $trip) {
            return $this->error(null, "Data not found!", 404);
        }

        $trip->update([
            'status' => TripStatus::COMPLETED,
        ]);

        return $this->success(null, "Trip Completed Successfully", 200);
    }

    public function getUpcomingTrips($userId)
    {
        $date = request()->query('date');

        $query = Trip::with(
                [
                    'user',
                    'vehicle',
                    'tripBookings.user',
                    'departureRegion.state',
                    'destinationRegion.state',
                    'manifests'
                ]
            )
            ->where('user_id', $userId)
            ->whereDate('departure_at', '>', now())
            ->orWhereDate('start_date', '>', now());

        if ($date) {
            $query->whereDate('created_at', '=', $date);
        }

        $trips = $query->get();

        $data = TripResource::collection($trips);

        return $this->success($data, "Upcoming trips", 200);
    }

    public function getCompletedTrips($userId)
    {
        $trips = Trip::with(
                [
                    'user',
                    'vehicle',
                    'tripBookings.user',
                    'departureRegion.state',
                    'destinationRegion.state',
                    'manifests'
                ]
            )
            ->where('user_id', $userId)
            ->where('status', TripStatus::COMPLETED)
            ->get();

        $data = TripResource::collection($trips);

        return $this->success($data, "Completed trips", 200);
    }

    public function getCancelledTrips($userId)
    {
        $trips = Trip::with(
                [
                    'user',
                    'vehicle',
                    'tripBookings.user',
                    'departureRegion.state',
                    'destinationRegion.state',
                    'manifests'
                ]
            )
            ->where('user_id', $userId)
            ->where('status', TripStatus::CANCELLED)
            ->get();

        $data = TripResource::collection($trips);

        return $this->success($data, "Completed trips", 200);
    }

    public function getAllTrips($userId)
    {
        $type = request()->query('type');
        $date = request()->query('date');
        $departure = request()->query('departure');
        $destination = request()->query('destination');

        $query = Trip::with(
                [
                    'user',
                    'vehicle',
                    'tripBookings.user',
                    'departureRegion.state',
                    'destinationRegion.state',
                    'manifests'
                ]
            )
            ->where('user_id', $userId)
            ->where('status', TripStatus::ACTIVE)
            ->where(function ($query) {
                $date = request()->query('date', date('Y-m-d'));
                $time = request()->query('time', '00:00:00');
                $departureAt = "$date $time";

                $query->where('departure_at', '>=', $departureAt)
                    ->orWhere('start_date', '>=', now());
            });

        if ($type) {
            $query->where('type', $type);
        }

        if ($date) {
            $query->whereDate('created_at', $date);
        }

        if ($departure) {
            $query->where('departure', $departure);
        }

        if ($destination) {
            $query->where('destination', $destination);
        }

        $trips = $query->get();

        if ($type === TripType::RECURRING) {
            $data = RecurringTripResource::collection($trips);
        } elseif ($type === TripType::ONETIME) {
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
        $departure = request()->query('departure');
        $destination = request()->query('destination');

        $query = Trip::with(
                [
                    'user',
                    'vehicle',
                    'tripBookings.user',
                    'departureRegion.state',
                    'destinationRegion.state',
                    'manifests'
                ]
            )
            ->where('status', TripStatus::ACTIVE)
            ->where(function ($query) {
                $date = request()->query('date', date('Y-m-d'));
                $time = request()->query('time', '00:00:00');
                $departureAt = "$date $time";

                $query->where('departure_at', '>=', $departureAt)
                    ->orWhere('start_date', '>=', now());
            });

        if ($type) {
            $query->where('type', $type);
        }

        if ($date) {
            $query->whereDate('created_at', $date);
        }

        if ($departure) {
            $query->where('departure', $departure);
        }

        if ($destination) {
            $query->where('destination', $destination);
        }

        $trips = $query->get();

        if ($type === TripType::RECURRING) {
            $data = RecurringTripResource::collection($trips);
        } elseif ($type === TripType::ONETIME) {
            $data = OneTimeTripResource::collection($trips);
        } else {
            $data = TripResource::collection($trips);
        }

        return $this->success($data, "All trips", 200);
    }

    public function getManifestInfo($tripId, $userId)
    {
        $trip = Trip::with(
                [
                    'user',
                    'vehicle',
                    'tripBookings.user',
                    'departureRegion.state',
                    'destinationRegion.state',
                    'manifests'
                ]
            )
            ->where('id', $tripId)
            ->first();

        if (! $trip) {
            return $this->error("Trip not found", 404);
        }

        $passenger = $trip->tripBookings
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
            'departure' => $trip->departure,
            'destination' => $trip->destination,
            'seat' => (int)$passenger->selected_seat,
        ];

        return $this->success($info, "Passenger Manifest Detail", 200);
    }

    public function startTrip($request)
    {
        $user = User::with(['transactions', 'driverTripPayments'])->findOrFail($request->user_id);

        $trip = Trip::with(['tripBookings.user', 'manifests'])->find($request->trip_id);

        if (!$trip) {
            return $this->error(null, "Data not found!", 404);
        }

        if ($user->wallet < self::TRIP_CHARGE_AMOUNT) {
            return $this->error(null, "Insufficient wallet balance!", 400);
        }

        try {
            DB::beginTransaction();

            if ($trip->tripBookings->isEmpty()) {
                return $this->error(null, "No bookings available!", 400);
            }

            foreach ($trip->tripBookings as $booking) {
                $manifest = new Manifest([
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

                $trip->manifests()->save($manifest);
            }

            $this->topUpWallet($user);
            $this->chargeWallet($user);

            $trip->update(['status' => TripStatus::INPROGRESS]);

            TripLog::create([
                'user_id' => $user->id,
                'trip_id' => $trip->id,
                'amount_charged' => self::TRIP_CHARGE_AMOUNT,
                'retry_attempt' => 1,
                'status' => 'success',
                'message' => 'Trip started successfully and manifest created.',
            ]);

            DB::commit();

            return $this->success(null, "Trip Started Successfully", 200);
        } catch (\Exception $e) {
            DB::rollBack();

            TripLog::create([
                'user_id' => $user->id,
                'trip_id' => $trip->id,
                'amount_charged' => 0,
                'retry_attempt' => 0,
                'status' => 'failure',
                'message' => "Failed to start trip: " . $e->getMessage(),
            ]);

            return $this->error(null, "Failed to start trip", 200);
        }
    }

    public function getBusStops($stateId)
    {
        $stops = BusStop::where('state_id', $stateId)->get();

        $data = $stops->map(function ($stop) {
            return $stop->stops;
        });

        return $this->success($data, "Bus stops");
    }

    public function getPopularTrips()
    {
        $trips = Trip::where('trips.status', 1)->limit(5)->inRandomOrder();
        return $this->success($trips->get(), "Trips");
    }
}
