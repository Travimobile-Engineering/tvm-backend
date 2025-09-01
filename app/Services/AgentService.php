<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\Trip;
use App\Models\User;
use App\Enum\TripType;
use App\Enum\UserType;
use App\Models\TripLog;
use App\Enum\TripStatus;
use App\DTO\SendCodeData;
use App\Enum\MailingEnum;
use App\Events\TripStart;
use App\Trait\AgentTrait;
use App\Trait\DriverTrait;
use App\Enum\PaymentMethod;
use App\Mail\VerifyPinMail;
use App\Models\TripBooking;
use App\Trait\HttpResponse;
use Illuminate\Support\Str;
use App\Enum\ManifestStatus;
use App\Events\TripCancelled;
use App\Models\RouteSubregion;
use App\Trait\TripBookingTrait;
use App\Events\PassengerTripStart;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Http\Resources\TripResource;
use Illuminate\Support\Facades\Hash;
use App\DTO\NotificationDispatchData;
use Illuminate\Support\Facades\Cache;
use App\Events\TripDepartureNotification;
use App\Http\Resources\TripBookingResource;
use Illuminate\Support\Facades\RateLimiter;
use App\Http\Resources\AgentProfileResource;
use App\Notifications\PassengerTripNotification;
use App\Services\Notification\NotificationDispatcher;

class AgentService
{
    use HttpResponse, TripBookingTrait, DriverTrait, AgentTrait;

    public function __construct(
        protected NotificationDispatcher $notifier
    ) {}

    public function profile()
    {
        $auth = authUser();
        $userId = $auth->id;

        $user = User::with([
                'transitCompany',
                'busStops.state',
                'userBank',
                'securityQuestion',
            ])
            ->where('id', $userId)
            ->first();

        if (!$user) {
            return $this->error(null, "User not found", 404);
        }

        if ($user->user_category !== UserType::AGENT->value) {
            return $this->error(null, "You are not allowed to access this resource", 403);
        }

        $data = new AgentProfileResource($user);

        return $this->success($data, "Agent profile");
    }

    public function getAgent($agentId)
    {
        $agent = User::with([
                'transitCompany',
                'busStops.state',
                'userBank',
            ])
            ->where('agent_id', $agentId)
            ->first();

        if (!$agent) {
            return $this->error(null, "Agent not found", 404);
        }

        $data = new AgentProfileResource($agent);

        return $this->success($data, "Agent details");
    }

    public function changePassword($request)
    {
        $user = User::find($request->user_id);

        if (! $user) {
            return $this->error(null, 'User not found', 404);
        }

        if (Hash::check($request->current_password, $user->password)) {
            $user->update([
                'password' => Hash::make($request->new_password),
            ]);

             return $this->success(null, "Password changed successfully");

        }else {
            return $this->error(null, 'Old Password did not match', 422);
        }
    }

    public function agentInfo($request)
    {
        $user = User::findOrFail($request->user_id);

        $photo = uploadFile($request, "profile_photo", "agent/profile");
        $agentId = generateUniqueNumber('users', 'agent_id', 11);

        $user->update([
            'agent_id' => $agentId,
            'profile_photo' => $photo['url'],
            'public_id' => $photo['public_id'],
            'gender' => $request->gender,
            'nin' => encryptData($request->nin) ?? null,
            'address' => $request->address,
            'next_of_kin_full_name' => $request->next_of_kin_full_name,
            'next_of_kin_relationship' => $request->next_of_kin_relationship,
            'next_of_kin_phone_number' => $request->next_of_kin_phone_number,
            'referral_code' => generateUniqueString('users', 'referral_code', 8),
        ]);

        return $this->success(null, "Agent information updated successfully");
    }

    public function busSearch($request)
    {
        $trips = Trip::where('departure', $request->departure)
            ->where('destination', $request->destination)
            ->where('departure_date', $request->departure_date)
            ->where('departure_time', $request->departure_time)
            ->where('status', TripStatus::UPCOMING)
            ->defaultWithRelations()
            ->get();

        $data = TripResource::collection($trips);

        return $this->success($data, "Bus search result");
    }

    public function buyTicket($request)
    {
        $user = authUser();
        $amount_paid = $request->amount_paid;
        $result = null;
        $paymentProcessor = null;

        $user = User::with(['transactions', 'walletAccount'])
                ->findOrFail($user->id);

        if ($user->user_category == UserType::AGENT->value && $user->wallet_balance < 1000) {
            return $this->error(null, "Your wallet balance must be at least 1000 to make a booking", 400);
        }

        match($request->payment_method) {
            PaymentMethod::WALLET => $result = $this->walletPayment($amount_paid, $request, $user),
            default => throw new \Exception('Invalid payment method'),
        };

        return $this->processPayment($request, $result, $paymentProcessor);
    }

    public function ticketSearch($request)
    {
        $user = authUser();
        $query = $request->input('search');

        $tickets = TripBooking::with('user')
            ->where('agent_id', $user->id)
            ->where(function ($q) use ($query) {
                $q->where('booking_id', 'LIKE', "%{$query}%")
                    ->orWhereHas('user', function ($q) use ($query) {
                        $q->where('first_name', 'LIKE', "%{$query}%")
                            ->orWhere('last_name', 'LIKE', "%{$query}%");
                    });
            })
            ->get();

        $data = TripBookingResource::collection($tickets);

        return $this->success($data, "Ticket search result");
    }

    public function searchPassenger($request)
    {
        $search = $request->input('search');

        $formattedPhone = is_numeric($search) ? formatPhoneNumber($search) : null;

        $users = User::select('id', 'first_name', 'last_name', 'phone_number', 'email', 'gender', 'profile_photo')
            ->where(function ($query) use ($search, $formattedPhone) {
                if ($formattedPhone) {
                    $query->where('phone_number', $formattedPhone);
                }

                $query->orWhere('first_name', 'LIKE', "%{$search}%")
                    ->orWhere('last_name', 'LIKE', "%{$search}%");
            })
            ->get();

        return $this->success($users, "Passenger search result");
    }

    public function addUser($request)
    {
        $fullName = trim($request->input('name'));
        $nameParts = explode(' ', $fullName, 2);

        $firstName = $nameParts[0] ?? null;
        $lastName = $nameParts[1] ?? null;

        $user = User::create([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'phone_number' => formatPhoneNumber($request->phone_number),
            'gender' => $request->gender,
            'nin' => $request->nin,
            'next_of_kin_full_name' => $request->next_of_kin_full_name,
            'next_of_kin_phone_number' => $request->next_of_kin_phone_number,
            'next_of_kin_gender' => $request->next_of_kin_gender,
            'next_of_kin_relationship' => $request->next_of_kin_relationship,
            'verification_code' => 0000,
            'profile_photo' => null,
            'password' => Str::password(10),
        ]);

        return $this->success($user, "User created successfully");
    }

    public function bookingHistory($userId)
    {
        $status = strtolower(request()->query('status', ''));

        $bookings = TripBooking::with([
                'user' => function ($query) {
                    $query->select('id', 'first_name', 'last_name', 'phone_number');
                },
                'trip' => function ($query) {
                    $query->select('id', 'departure', 'destination', 'departure_date', 'departure_time', 'trip_duration', 'status');
                },
            ])
            ->where('agent_id', $userId)
            ->when($status, function ($query) use ($status) {
                if ($status === 'upcoming') {
                    $query->whereHas('trip', function ($tripQuery) {
                        $tripQuery->whereDate('departure_date', '>', now())
                                  ->orWhereDate('start_date', '>', now());
                    });
                } else {
                    $query->whereHas('trip', fn($tripQuery) => $tripQuery->where('status', $status));
                }
            })
            ->get();

        return $this->success($bookings, 'Booking History Fetched Successfully');
    }

    public function bookingDetail($bookingId)
    {
        $booking = TripBooking::with([
                'user:id,first_name,last_name,phone_number',
                'trip:id,vehicle_id,departure,destination,departure_date,departure_time,trip_duration,reason,date_cancelled,status',
                'trip.vehicle:id,user_id,name,year,model,color,type,capacity,plate_no,seats,seat_row,seat_column',
                'trip.vehicle.user:id,first_name,last_name,email,phone_number,profile_photo',
            ])
            ->where('booking_id', $bookingId)
            ->first();

        if (! $booking) {
            return $this->error('Booking not found', 404);
        }

        $driver = [
            'driver' => $booking->trip?->vehicle?->user ?? null
        ];

        $vehicle = [
            'vehicle' => $booking->trip?->vehicle ?? null
        ];

        $bookingData = $booking->toArray();
        unset($bookingData['trip']['vehicle']);
        $bookingData['amount_paid'] = $booking->total_amount_paid;
        $bookingData = array_merge($bookingData, $driver, $vehicle);

        return $this->success($bookingData, 'Booking History Fetched Successfully');
    }

    public function cancelTrip($request, $tripId)
    {
        $trip = Trip::find($tripId);

        if (! $trip) {
            return $this->error('Trip not found', 404);
        }

        $trip->update([
            'reason' => $request->reason,
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

        return $this->success($trip, 'Trip cancelled successfully');
    }

    public function updateProfile($request)
    {
        $user = User::find($request->user_id);

        if (! $user) {
            return $this->error(null, 'User not found', 404);
        }

        $photo = [
            'url' => $user->profile_photo,
            'public_id' => $user->public_id,
        ];

        if ($request->hasFile('profile_photo')) {
            $photo = uploadFile($request, "profile_photo", "agent/profile");
        }

        $user->update([
            'first_name' => $request->first_name ?? $user->first_name,
            'last_name' => $request->last_name ?? $user->last_name,
            'email' => $request->email ?? $user->email,
            'phone_number' => $request->phone_number ?? $user->phone_number,
            'gender' => $request->gender ?? $user->gender,
            'nin' => encryptData($request->nin) ?? $user->nin,
            'profile_photo' => $photo['url'],
            'public_id' => $photo['public_id'],
            'next_of_kin_full_name' => $request->next_of_kin_full_name ?? $user->next_of_kin_full_name,
            'next_of_kin_relationship' => $request->next_of_kin_relationship ?? $user->next_of_kin_relationship,
            'next_of_kin_phone_number' => $request->next_of_kin_phone_number ?? $user->next_of_kin_phone_number,
        ]);

        return $this->success($user, "Profile updated successfully");
    }

    public function deleteProfile($request)
    {
        $user = User::where('id', $request->user_id)
            ->where('agent_id', $request->agent_id)
            ->first();

        if (! $user) {
            return $this->error(null, 'User not found', 404);
        }

        $user->delete();
        return $this->success(null, "Account deleted successfully");
    }

    public function sendOtp($request)
    {
        $user = User::find($request->user_id);

        if (! $user) {
            return $this->error(null, 'User not found', 404);
        }

        $code = generateUniqueNumber('users', 'verification_code', 5);
        Cache::put('otp_pin_' . $user->id, $code, now()->addMinutes(5));

        $user->update([
            'verification_code' => $code,
            'verification_code_expires_at' => now()->addMinutes(10),
        ]);

        $data = [
            'name' => $user->first_name,
            'code' => $code
        ];

        sendCode($request, new SendCodeData(
            type: MailingEnum::VERIFY_OTP,
            user: $user,
            data: $data,
            phone: $request->phone_number,
            message: "Your Travi Verification Pin is: $code. Valid for 10 mins. Do not share with anyone. Powered By Travi",
            subject: 'Verify OTP',
            mailable: VerifyPinMail::class,
        ));

        return $this->success(null, 'Verification code sent successfully');
    }

    public function verifyPin($request)
    {
        $user = User::where('id', $request->user_id)
            ->where('verification_code', $request->code)
            ->whereFuture('verification_code_expires_at')
            ->first();

        if (! $user) {
            return $this->error(null, "Invalid code", 400);
        }

        $cachedOTP = Cache::get('otp_pin_' . $user->id);

        if (!$cachedOTP || $cachedOTP !== $request->code) {
            return $this->error(null, "Invalid OTP", 400);
        }

        Cache::put('pin_change_verified_' . $user->id, true, now()->addMinutes(5));
        Cache::forget('otp_pin_' . $user->id);

        $user->update([
            'verification_code' => 0,
            'verification_code_expires_at' => null
        ]);

        return $this->success(null, 'Verified successfully');
    }

    public function changePin($request)
    {
        $user = User::with('userPin')->findOrFail($request->user_id);

        $user->userPin()->update([
            'pin' => bcrypt($request->pin)
        ]);

        Cache::forget('pin_change_verified_' . $user->id);

        return $this->success(null, 'Changed successfully');
    }

    public function searchDriver($request)
    {
        $search = $request->input('search');

        $users = User::select('id', 'first_name', 'last_name', 'profile_photo')
            ->with('vehicle:id,user_id,plate_no,model,color')
            ->where('user_category', UserType::DRIVER->value)
            ->where('is_premium_driver', false)
            ->where(function ($query) use ($search) {
                $query->where('phone_number', $search)
                    ->orWhere('first_name', 'LIKE', "%{$search}%")
                    ->orWhere('last_name', 'LIKE', "%{$search}%");
            })
            ->get();

        return $this->success($users, "Drivers search result");
    }

    public function impersonateDriver($request)
    {
        $driverId = $request->user_id;
        $cacheKey = "impersonation_attempts:driver_{$driverId}";

        $driver = User::with(['vehicle'])->where('id', $request->user_id)
                  ->where('user_category', UserType::DRIVER->value)
                  ->first();

        if (! $driver) {
            return $this->error(null, 'Driver not found', 404);
        }

        if (!Hash::check($request->password, $driver->password)) {
            RateLimiter::hit($cacheKey, 86400);

            return $this->error(
                null,
                "Invalid password, Attempts left: " . (3 - RateLimiter::attempts($cacheKey)),
                401
            );
        }

        RateLimiter::clear($cacheKey);
        $token = JWTAuth::fromUser($driver);

        return $this->success([
            'token' => $token,
            'driver' => $driver,
            'wallet_setup' => hasSetupWallet($driver->id),
        ], 'Logged in successfully');
    }

    public function createOneTimeTrip($request)
    {
        if ($request->departure_id == $request->destination_id) {
            return $this->error("Departure and destination cannot be the same", 400);
        }

        try {
            $user = User::with(['transitCompany', 'vehicle'])
                ->findOrFail($request->user_id);

            $route = RouteSubregion::with('state')->findOrFail($request->destination_id);

            $trip = Trip::create([
                'user_id' => $user->id,
                'agent_id' => $request->agent_id ?? null,
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

            return $this->success($trip, "Created successfully", 201);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function createRecurringTrip($request)
    {
        if ($request->departure_id == $request->destination_id) {
            return $this->error("Departure and destination cannot be the same", 400);
        }

        try {

            $user = User::with(['transitCompany', 'vehicle'])
                ->findOrFail($request->user_id);

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
                        'agent_id' => $request->agent_id ?? null,
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

            return $this->success(null, "Recurring trips created successfully", 201);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function getTrips($userId)
    {
        $date = request()->query('date');
        $status = request()->query('status', TripStatus::UPCOMING);

        if (!in_array($status, [
                TripStatus::UPCOMING,
                TripStatus::COMPLETED,
                TripStatus::CANCELLED,
                TripStatus::INPROGRESS,
            ]
        )) {
            return $this->error("Invalid status", 400);
        }

        $trips = Trip::where('user_id', $userId)
            ->defaultWithRelations()
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($date, function ($q, $date) {
                $start = Carbon::parse($date)->startOfDay();
                $end   = Carbon::parse($date)->endOfDay();
                $q->whereBetween('created_at', [$start, $end]);
            })
            ->paginate(25);

        $data = TripResource::collection($trips);

        return $this->withPagination($data, "Trips");
    }

    public function tripDetails($tripId)
    {
        $trip = Trip::where('id', $tripId)
            ->defaultWithRelations()
            ->firstOrFail();

        $data = new TripResource($trip);
        return $this->success($data, "Trip details");
    }

    public function startTrip($request)
    {
        $user = User::with(['transactions', 'driverTripPayments'])
            ->findOrFail($request->user_id);

        $trip = Trip::with(relations: ['user', 'tripBookings' => function ($query) {
                $query->where('payment_status', 1);
            }, 'manifest', 'departureRegion', 'destinationRegion', 'departureRegion.state', 'destinationRegion.state'])
            ->find($request->trip_id);

        if (! $trip) {
            return $this->error(null, "Trip not found!", 404);
        }

        if ($trip->status !== TripStatus::UPCOMING) {
            return $this->error(null, "Sorry " . $trip->status, 400);
        }

        if ($request->payment_method === 'driver_wallet' && $user->wallet_amount < $request->amount) {
            return $this->error(null, "Insufficient wallet balance!", 400);
        }

        try {
            DB::beginTransaction();

            if ($trip->tripBookings->isEmpty()) {
                return $this->error(null, "No bookings available!", 400);
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
                'amount_charged' => $request->amount ?? getFee('manifest'),
                'retry_attempt' => 1,
                'status' => 'success',
                'message' => 'Trip started successfully and manifest created.',
            ]);

            if (in_array($request->payment_method, [PaymentMethod::DRIVERWALLET, PaymentMethod::WALLET])) {
                $this->chargeWallet($user, $request->amount, $trip, $trip->user);
            }

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

    public function updateNotification($request)
    {
        $user = User::find($request->user_id);

        if (!$user) {
            return $this->error(null, "User not found", 404);
        }

        $user->update([
            'inbox_notifications' => $request->inbox_notifications,
            'email_notifications' => $request->email_notifications,
        ]);

        return $this->success(null, "Notification settings updated successfully");
    }

    public function notifyPassengers($request)
    {
        $trip = Trip::with(['tripBookings'])->findOrFail($request->trip_id);

        foreach ($trip->tripBookings as $booking) {
            $booking->user?->notify(new PassengerTripNotification($trip, $request));
        }

        $passengerUsers = $trip->tripBookings
            ->pluck('user')
            ->filter()
            ->unique('id');

        $this->notifier->send(new NotificationDispatchData(
            events: [
                [
                    'class' => TripDepartureNotification::class,
                    'payload' => [
                        'type' => 'trip_departure',
                        'message' => 'Your trip is about to depart.',
                        'tripId' => $trip->id,
                    ],
                ]
            ],
            recipients: $passengerUsers,
            title: 'Trip Departure',
            body: 'Your trip is about to depart.',
            data: [
                'trip_id' => $trip->id,
                'type' => 'trip_departure',
            ]
        ));

        return $this->success(null, "Notification sent successfully");
    }

    public function scanTicket($request, $bookingId, $seatNo)
    {
        $ticketId = $bookingId ?? $request->input('booking_id');
        $seatNo = $seatNo ?? $request->input('seat_no');

        if (!$ticketId) {
            return $this->error(null, "Ticket ID is required", 400);
        }

        if (!$seatNo) {
            return $this->error(null, "Passenger ID is required", 400);
        }

        $booking = TripBooking::with('tripBookingPassengers')
            ->where('booking_id', $ticketId)
            ->first();

        if (!$booking) {
            return $this->error(null, "Booking not found", 404);
        }

        $passenger = $booking->tripBookingPassengers()
            ->where('selected_seat', $seatNo)
            ->first();

        if (!$passenger) {
            return $this->error(null, "Passenger not found", 404);
        }

        $passenger->update(['on_seat' => true]);

        return $this->success(null, "Ticket scanned successfully");
    }

    public function validateDriverPin($request)
    {
        $user = User::with('userPin')->find($request->user_id);

        if (! $user) {
            return $this->error(null, "User not found", 404);
        }

        if (! $user->userPin) {
            return $this->validatePassword($user, $request);
        }

        $userKey = 'login_attempts:' . $user->id;
        $blockKey = 'login_blocked:' . $user->id;

        if (Cache::has($blockKey)) {
            return $this->error(null, "Too many attempts. Try again later.", 429);
        }

        if (Hash::check($request->pin, $user?->userPin->pin)) {
            Cache::forget($userKey);
            return $this->success(null, "Valid credentials");
        }

        $attempts = Cache::increment($userKey);

        if ($attempts === 1) {
            Cache::put($userKey, 1, now()->addMinutes(5));
        }

        if ($attempts >= 3) {
            Cache::put($blockKey, now()->addMinutes(10)->timestamp, 600);
            Cache::forget($userKey);
            return $this->error(null, "Too many attempts. You are blocked", 429);
        }

        return $this->error(null, "Invalid credentials entered.", 400);
    }
}





