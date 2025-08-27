<?php

namespace App\Services\Trip;

use App\DTO\NotificationDispatchData;
use App\Enum\ManifestStatus;
use App\Models\Trip;
use App\Enum\TripType;
use App\Enum\TripStatus;
use App\Events\PassengerTripStart;
use App\Events\TripCancelled;
use App\Events\TripCreated;
use App\Events\TripStart;
use App\Trait\HttpResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\TripResource;
use App\Http\Resources\OneTimeTripResource;
use App\Http\Resources\RecurringTripResource;
use App\Models\BusStop;
use App\Models\Manifest;
use App\Models\RouteSubregion;
use App\Models\TripBooking;
use App\Models\TripLog;
use App\Models\User;
use App\Services\Notification\NotificationDispatcher;
use App\Trait\DriverTrait;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Spatie\ResponseCache\Facades\ResponseCache;

class TripService
{
    use HttpResponse, DriverTrait;

    public function __construct(
        protected NotificationDispatcher $notifier
    ) {}

    public function createOneTime($request)
    {
        if ($request->departure_id == $request->destination_id) {
            return $this->error(null, "Departure and destination cannot be the same", 400);
        }

        try {
            $user = User::with(['transitCompany', 'vehicle'])->findOrFail($request->user_id);

            if (!$user instanceof User) {
                $user = User::findOrFail($user->id);
            }

            $route = RouteSubregion::with('state')->findOrFail($request->destination_id);

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
                'zone_id' => $route->state?->zone_id,
                'type' => TripType::ONETIME,
                'status' => TripStatus::UPCOMING,
            ]);

            $this->notifier->send(new NotificationDispatchData(
                events: [
                    [
                        'class' => TripCreated::class,
                        'payload' => [
                            'type' => 'trip_create',
                            'message' => 'Trip created successfully',
                            'userId' => $user->id,
                        ],
                    ]
                ],
                recipients: $user,
                title: 'Trip Created',
                body: 'Your trip has been created successfully',
                data: [
                    'trip_id' => $trip->id,
                    'type' => 'trip_created',
                ]
            ));

            return $this->success($trip, "Created successfully", 201);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return $this->error('Something went wrong: ' . $e->getMessage(), 500);
        }
    }

    public function getTrip(Trip $trip)
    {
        $trip->load([
            'user.transitCompany',
            'tripBookings' => fn ($q) => $q
                ->onlySuccessful()
                ->withUserAndPassengers(),
            'departureRegion.state',
            'destinationRegion.state',
            'manifest',
            'vehicle',
            'departureRegion.parksWithTransitCompany',
            'destinationRegion.parksWithTransitCompany',
        ]);

        $data = new TripResource($trip);

        return $this->success($data, "Trip details");
    }

    /**
     * Refactored to improve code readability, maintainability, and performance:
     * - Utilizes eager loading (`with`) to optimize database queries by reducing the number of queries.
     * - Replaces multiple conditional `where` calls with a more compact and dynamic closure for query construction.
     * - Filters data using query parameters (`request()->query`) for flexibility and adherence to RESTful practices.
     * - Applies a resource transformation (`TripResource`) for a consistent API response format.
     * - Ensures that queries are dynamically constructed based on input parameters, promoting reusability and clean code.
     */
    public function getTrips()
    {
        $date = request()->query('date');
        $time = request()->query('time');
        $departure = request()->query('departure');
        $destination = request()->query('destination');

        // Treat empty strings as nulls
        $hasDate = filled($date);
        $hasTime = filled($time);

        $trips = Trip::where('status', TripStatus::UPCOMING)
            ->defaultWithRelations()
            ->when(filled($departure), fn ($q) => $q->where('departure', $departure))
            ->when(filled($destination), fn ($q) => $q->where('destination', $destination))
            ->when($hasDate && !$hasTime, fn ($q) => $q->whereDate('departure_date', $date))
            ->when(!$hasDate && $hasTime, fn ($q) => $q->whereTime('departure_time', '>=', $time))
            ->when($hasDate && $hasTime, function ($q) use ($date, $time) {
                $q->where(function ($qq) use ($date, $time) {
                    $qq->whereDate('departure_date', '>', $date)
                    ->orWhere(function ($qqq) use ($date, $time) {
                        $qqq->whereDate('departure_date', $date)
                            ->whereTime('departure_time', '>=', $time);
                    });
                });
            })
            ->paginate(25);

        $data = TripResource::collection($trips);

        return $this->withPagination($data, 'Available trips', 200);
    }

    public function getOneTime($id)
    {
        $trip = Trip::with(
                [
                    'user.transitCompany',
                    'vehicle',
                    'tripBookings.user',
                    'departureRegion.state',
                    'destinationRegion.state',
                    'manifest',
                    'departureRegion.parks',
                    'destinationRegion.parks',
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
                    'user.transitCompany',
                    'vehicle',
                    'tripBookings.user',
                    'departureRegion.state',
                    'destinationRegion.state',
                    'departureRegion.parks',
                    'destinationRegion.parks',
                    'manifest'
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
            'price' => $request->ticket_price,
            'bus_stops' => $request->bus_stops,
        ]);

        return $this->success(null, "Updated Successfully", 200);
    }

    public function createRecurring($request)
    {
        if ($request->departure_id == $request->destination_id) {
            return $this->error(null, "Departure and destination cannot be the same", 400);
        }

        try {

            $user = User::with(['transitCompany', 'vehicle'])->findOrFail($request->user_id);

            $startDate = Carbon::parse($request->start_date);
            $endDate = $startDate->copy()->addMonths($request->reoccur_duration);
            $tripSchedule = $request->trip_days;

            $route = RouteSubregion::with('state')->findOrFail($request->destination_id);

            foreach ($request->trip_days as $tripDay) {
                if (!is_array($tripDay) || !isset($tripDay['day'], $tripDay['time'])) {
                    return $this->error("Invalid trip_days format. Expected array of {day, time}", 422);
                }

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
                        'departure_date' => $tripDateTime->format('Y-m-d'),
                        'departure_time' => $time,
                        'trip_days' => [$day],
                        'trip_schedule' => $tripSchedule,
                        'reoccur_duration' => $request->reoccur_duration,
                        'bus_type' => $request->bus_type,
                        'price' => $request->price,
                        'bus_stops' => $request->bus_stops ?? [],
                        'means' => $request->means ?? 1,
                        'zone_id' => $route->state?->zone_id,
                        'type' => TripType::RECURRING,
                        'status' => TripStatus::UPCOMING,
                    ]);

                    $currentDate->addWeek();
                }
            }

            $this->notifier->send(new NotificationDispatchData(
                events: [
                    [
                        'class' => TripCreated::class,
                        'payload' => [
                            'type' => 'trip_create',
                            'message' => 'Trip created successfully',
                            'userId' => $user->id,
                        ],
                    ]
                ],
                recipients: $user,
                title: 'Trip Created',
                body: 'Your trip has been created successfully',
                data: [
                    'type' => 'trip_created',
                ]
            ));

            return $this->success(null, "Recurring trips created successfully", 201);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function getRecurring($id)
    {
        $trip = Trip::with(
                [
                    'user.transitCompany',
                    'vehicle',
                    'tripBookings.user',
                    'departureRegion.state',
                    'destinationRegion.state',
                    'manifest',
                    'departureRegion.parks',
                    'destinationRegion.parks',
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
                    'user.transitCompany',
                    'vehicle',
                    'tripBookings.user',
                    'departureRegion.state',
                    'destinationRegion.state',
                    'manifest',
                    'departureRegion.parks',
                    'destinationRegion.parks',
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
        $trip = Trip::with('user')->find($id);

        if (! $trip) {
            return $this->error(null, "Data not found!", 404);
        }

        $trip->update([
            'reason' => $request->reason,
            'date_cancelled' => now(),
            'status' => TripStatus::CANCELLED,
        ]);

        $this->notifier->send(new NotificationDispatchData(
            events: [
                [
                    'class' => TripCancelled::class,
                    'payload' => [
                        'type' => 'trip_cancelled',
                        'message' => 'Your trip has been cancelled.',
                        'tripId' => $trip->id,
                    ],
                ]
            ],
            recipients: $trip?->user,
            title: 'Trip Cancelled',
            body: 'Your trip has been cancelled.',
            data: [
                'type' => 'trip_cancelled',
            ]
        ));

        return $this->success(null, "Trip Cancelled Successfully", 200);
    }

    public function completeTrip($id)
    {
        $trip = Trip::with('tripBookings')->find($id);

        if (! $trip) {
            return $this->error(null, "Data not found!", 404);
        }

        $trip->update([
            'status' => TripStatus::COMPLETED,
        ]);

        $trip->tripBookings->each(function ($booking) {
            $booking->update([
                'status' => 2,
            ]);
        });

        ResponseCache::clear();

        return $this->success(null, "Trip Completed Successfully", 200);
    }

    public function startTrip($request)
    {
        $user = User::with(['transactions', 'driverTripPayments'])->findOrFail($request->user_id);

        $existingTrip = Trip::hasOngoingTrip($request->trip_id, $user->id);

        if ($existingTrip) {
            return $this->error(null, "You have an ongoing trip. Complete it before starting a new one.", 400);
        }

        $trip = Trip::with([
                'tripBookings' => function ($query) {
                    $query->where('payment_status', 1)
                        ->with('tripBookingPassengers');
                },
                'manifest',
                'departureRegion',
                'destinationRegion',
                'departureRegion.state',
                'destinationRegion.state',
            ])->find($request->trip_id);

        if (!$trip) {
            return $this->error(null, "Trip not found!", 404);
        }

        if ($trip->status !== TripStatus::UPCOMING) {
            return $this->error(null, "Sorry " . $trip->status, 400);
        }

        // $currentDateTime = now();
        // $tripDepartureDateTime = Carbon::parse("{$trip->departure_date} {$trip->departure_time}");

        // if (!$currentDateTime->equalTo($tripDepartureDateTime)) {
        //     return $this->error(null, "Cannot start trip. Current date and time do not match the scheduled departure.", 400);
        // }

        if ($user->wallet_amount < getFee('manifest')) {
            return $this->error(null, "Insufficient wallet balance!", 400);
        }

        try {
            DB::beginTransaction();

            if ($trip->tripBookings->isEmpty()) {
                return $this->error(null, "No bookings available!", 400);
            }

            $notSeatedPassengers = $trip->tripBookings
                ->flatMap->tripBookingPassengers
                ->filter(fn($passenger) => !$passenger->on_seat);

            if ($notSeatedPassengers->isNotEmpty()) {
                return $this->error(null, "Cannot start trip. All passengers must be seated.", 400);
            }

            foreach ($trip->tripBookings as $booking) {
                $booking->update(['manifest_status' => ManifestStatus::COMPLETED]);
            }

            $trip->manifest()->create([
                'status' => ManifestStatus::COMPLETED,
            ]);

            $trip->update(['status' => TripStatus::INPROGRESS]);

            TripLog::create([
                'user_id' => $user->id,
                'trip_id' => $trip->id,
                'amount_charged' => getFee('manifest'),
                'retry_attempt' => 1,
                'status' => 'success',
                'message' => 'Trip started successfully and manifest created.',
            ]);

            $this->chargeWallet($user, null, $trip);

            DB::commit();

            $passengerUsers = $trip->tripBookings
                ->pluck('user')
                ->filter()
                ->unique('id');

            $recipients = collect([$user])->merge($passengerUsers);

            $this->notifier->send(new NotificationDispatchData(
                events: [
                    [
                        'class' => TripStart::class,
                        'payload' => [
                            'type' => 'trip_start',
                            'message' => 'Trip started successfully.',
                            'tripId' => $trip->id
                        ],
                    ],
                    [
                        'class' => PassengerTripStart::class,
                        'payload' => [
                            'type' => 'trip_start',
                            'message' => 'Trip started successfully.',
                            'tripId' => $trip->id
                        ],
                    ],
                ],
                recipients: $recipients,
                title: 'Trip Started',
                body: 'The trip has begun.',
                data: [
                    'trip_id' => $trip->id,
                    'type' => 'trip_started'
                ]
            ));

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

            return $this->error(null, "Failed to start trip: " . $e->getMessage(), 400);
        }
    }

    public function getUpcomingTrips($userId)
    {
        $date = request()->query('date');

        $query = Trip::where('user_id', $userId)
            ->defaultWithRelations()
            ->where('status', TripStatus::UPCOMING);

        if ($date) {
            $query->whereDate('created_at', '=', $date);
        }

        $trips = $query->get();

        $data = TripResource::collection($trips);

        return $this->success($data, "Upcoming trips");
    }

    public function getCompletedTrips($userId)
    {
        $trips = Trip::where('user_id', $userId)
            ->defaultWithRelations()
            ->where('status', TripStatus::COMPLETED)
            ->get();

        $data = TripResource::collection($trips);

        return $this->success($data, "Completed trips", 200);
    }

    public function getCancelledTrips($userId)
    {
        $trips = Trip::where('user_id', $userId)
            ->defaultWithRelations()
            ->where('status', TripStatus::CANCELLED)
            ->get();

        $data = TripResource::collection($trips);

        return $this->success($data, "Completed trips", 200);
    }

    public function getAllTrips($userId)
    {
        $type = request()->query('type');
        $status = request()->query('status', TripStatus::UPCOMING);
        $date = request()->query('departure_date');
        $time = request()->query('departure_time');
        $departure = request()->query('departure');
        $destination = request()->query('destination');

        $trips = Trip::where('user_id', $userId)
            ->defaultWithRelations()
            ->where('status', $status)
            ->when($type, fn($q) => $q->where('type', $type))
            ->when($departure, fn($q) => $q->where('departure', $departure))
            ->when($destination, fn($q) => $q->where('destination', $destination))
            ->when($date && $time, function ($q) use ($date, $time) {
                $q->whereDate('departure_date', '>=', $date)
                ->whereTime('departure_time', '=', $time);
            })
            ->when($date && !$time, fn($q) => $q->whereDate('departure_date', $date))
            ->get();

        $data = TripResource::collection($trips);

        return $this->success($data, "All trips");
    }

    public function getAll()
    {
        $type = request()->query('type');
        $date = request()->query('date');
        $time = request()->query('time');
        $departure = request()->query('departure');
        $destination = request()->query('destination');

        $trips = Trip::where('status', TripStatus::UPCOMING)
            ->defaultWithRelations()
            ->when($type, function ($query, $type) {
                $query->where('type', $type);
            })
            ->when($departure, function ($query, $departure) {
                $query->where('departure', $departure);
            })
            ->when($destination, function ($query, $destination) {
                $query->where('destination', $destination);
            })
            ->when($date && $time, function ($query) use ($date, $time) {
                $query->where(function ($q) use ($date, $time) {
                    $q->whereDate('departure_date', '>=', $date)
                    ->whereTime('departure_time', '=', $time);
                });
            }, function ($query) use ($date) {
                $query->when($date, function ($q) use ($date) {
                    $q->whereDate('departure_date', $date);
                });
            })
            ->get();

        $data = match ($type) {
            TripType::RECURRING => RecurringTripResource::collection($trips),
            TripType::ONETIME   => OneTimeTripResource::collection($trips),
            default             => TripResource::collection($trips),
        };

        return $this->success($data, "All trips", 200);
    }

    public function getManifestInfo($tripId, $userId)
    {
        $trip = Trip::with([
                'user.transitCompany',
                'vehicle',
                'tripBookings.user',
                'departureRegion.state',
                'destinationRegion.state',
                'manifest',
                'departureRegion.parks',
                'destinationRegion.parks',
            ])
            ->where('id', $tripId)
            ->first();

        if (!$trip) {
            return $this->error("Trip not found", 404);
        }

        $passenger = $trip->tripBookings
            ->where('user_id', $userId)
            ->where('payment_status', 1)
            ->where('manifest_status', ManifestStatus::COMPLETED)
            ->first();

        if (!$passenger) {
            return $this->error("You do not have permission to complete this request or no valid booking found.", 400);
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

    public function getBusStops($stateId)
    {
        $driverId = request()->query('driver_id');
        $userId = $driverId ?: authUser()->id;

        $stops = BusStop::where('user_id', $userId)
            ->where('state_id', $stateId)
            ->get();

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

    public function downloadTicket($bookingId)
    {
        $booking = TripBooking::with([
            'user',
            'trip.user.transitCompany',
            'trip.vehicle',
            'trip.departureRegion.state',
            'trip.destinationRegion.state',
            'trip.departureRegion.parks',
            'trip.destinationRegion.parks',
            ])
            ->where('booking_id', $bookingId)
            ->first();

        if (!$booking) {
            return $this->error(null, "Booking not found", 404);
        }
        $pdf = Pdf::loadView('ticket.booking', [
            'company' => $booking->trip?->user?->transitCompany?->name,
            'profile_photo' => $booking->trip?->user?->profile_photo,
            'vehicle' => $booking->trip?->vehicle?->model,
            'air_conditioned' => $booking->trip?->vehicle?->air_conditioned ? 'AC' : 'Non-AC',
            'departure' => $booking->trip?->departureRegion?->name,
            'destination' => $booking->trip?->destinationRegion?->name,
            'departure_park' => $booking->trip?->departureRegion?->parks?->pluck('name')->join(', '),
            'destination_park' => $booking->trip?->destinationRegion?->parks?->pluck('name')->join(', '),
            'departure_date' => $booking->trip?->departure_date,
            'departure_time' => $booking->trip?->departure_time,
            'duration' => $booking->trip?->trip_duration,
            'passenger' => $booking->user?->first_name . ' ' . $booking->user?->last_name,
            'seat' => $booking->selected_seat,
            'ticket_id' => $booking->booking_id,
            'bus_number' => $booking->trip?->vehicle?->plate_no,
            'price' => $booking->trip?->price,
        ]);
        $fileName = 'ticket_' . str_replace(' ', '_', $booking->user?->first_name) . '_' . $booking->booking_id . '.pdf';

        return $pdf->download($fileName);
    }

    public function extendTime($request)
    {
        $user = User::findOrFail($request->user_id);

        $user->update([
            'trip_extended_time' => $request->trip_extended_time,
        ]);

        return $this->success(null, "Settings saved successfully");
    }

    public function tripExtendTime($request)
    {
        $trip = Trip::findOrFail($request->trip_id);

        $tripExtendTime = $request->trip_extended_time;
        if (!$tripExtendTime || !preg_match('/^\d{1,2}:\d{2}$/', $tripExtendTime)) {
            return $this->error(null, "Invalid time format. Please use HH:MM format.", 400);
        }

        $departureTime = Carbon::parse($trip->departure_time);
        list($hours, $minutes) = explode(':', $tripExtendTime);

        $totalMinutes = ((int) $hours * 60) + (int) $minutes;
        $newDepartureTime = $departureTime->addMinutes($totalMinutes);

        $trip->update(['departure_time' => $newDepartureTime->format('H:i')]);

        return $this->success(null, "Trip extended successfully");
    }
}
