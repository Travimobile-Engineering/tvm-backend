<?php

namespace App\Services;

use App\Enum\PaymentType;
use App\Enum\TripStatus;
use App\Http\Resources\CharterResource;
use App\Http\Resources\PremiumHireBookingResource;
use App\Http\Resources\PremiumHireTripResource;
use App\Http\Resources\PremiumHireVehicleResource;
use App\Models\Charter;
use App\Models\PaymentLog;
use App\Models\PremiumHireBooking;
use App\Models\PremiumHireBookingPassenger;
use App\Models\PremiumHireManifest;
use App\Models\PremiumHireRating;
use App\Models\PremiumUpgrade;
use App\Models\User;
use App\Models\Vehicle\Vehicle;
use App\Trait\DriverTrait;
use App\Trait\HttpResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Unicodeveloper\Paystack\Facades\Paystack;

class PremiumHireService
{
    use HttpResponse, DriverTrait;

    const TRIP_CHARGE_AMOUNT = 1000;

    public function vehicleLookup($request)
    {
        $latitude = $request->lat;
        $longitude = $request->lng;
        $seatCount = $request->vehicle_seat;
        $radius = 10;

        $nearbyUsers = User::select('id')
        ->selectRaw("(
            6371 * acos(
                cos(radians(?)) * cos(radians(lat)) * cos(radians(lng) - radians(?)) +
                sin(radians(?)) * sin(radians(lat))
            )
        ) AS distance", [$latitude, $longitude, $latitude])
        ->having("distance", "<", $radius)
        ->pluck('id');

        $vehicles = Vehicle::whereIn('user_id', $nearbyUsers)
            ->whereRaw("JSON_LENGTH(seats) = ?", [$seatCount])
            ->with([
                'premiumUpgrades.vehicle.vehicleImages',
            ])
            ->get();

        $data = $vehicles->flatMap(function ($vehicle) {
            return $vehicle->premiumUpgrades->map(function () use ($vehicle) {
                return [
                    'vehicle_id' => $vehicle->id,
                    'vehicle_model' => $vehicle->model,
                    'ac' => $vehicle->ac,
                    'seats' => is_array($seats = $vehicle->seats) ? count($seats) : 0,
                    'image' => $vehicle->vehicleImages()->value('url'),
                    'rating' => 3.5,
                ];
            });
        });

        return $this->success($data, "Vehicle");
    }

    public function vehicleDetail($id)
    {
        $vehicle = PremiumUpgrade::with(['vehicle.vehicleImages'])
            ->where('vehicle_id', $id)
            ->first();

        if (!$vehicle) {
            return $this->error("Vehicle not found", 404);
        }

        $data = new PremiumHireVehicleResource($vehicle);

        return $this->success($data, "Vehicle");
    }

    public function addCharter($request)
    {
        $user = Auth::user();

        if ($user->id != $request->user_id) {
            return $this->error(null, 'Unauthorized action.', 401);
        }

        $vehicle = Vehicle::with('user')->findOrFail($request->vehicle_id);

        if(! $vehicle?->user->is_available) {
            return $this->error(null, 'Driver is not available', 400);
        }

        Charter::updateOrCreate([
            'user_id' => $user->id ?: null,
            'vehicle_id' => $request->vehicle_id,
        ]);

        return $this->success(null, "Vehicle added to charter");
    }

    public function getCharter($userId)
    {
        $user = Auth::user();

        if ($user->id != $userId) {
            return $this->error(null, 'Unauthorized action.', 401);
        }

        $items = Charter::with(['user', 'vehicle'])
            ->where('user_id', $userId)
            ->first();
        $data = new CharterResource($items);

        return $this->success($data, "Charter");
    }

    public function payCharter($request)
    {
        $amount = $request->input('amount') * 100;

        $callbackUrl = $request->input('redirect_url');
        if (!filter_var($callbackUrl, FILTER_VALIDATE_URL)) {
            return response()->json(['error' => 'Invalid callback URL'], 400);
        }

        $paymentDetails = [
            'email' => $request->input('email'),
            'amount' => $amount,
            'metadata' => json_encode([
                'user_id' => $request->input('user_id'),
                'vehicle_id' => $request->input('vehicle_id'),
                'ticket_type' => $request->input('ticket_type'),
                'lng' => $request->input('lng'),
                'lat' => $request->input('lat'),
                'bus_stops' => $request->input('bus_stops'),
                'luggage' => $request->input('luggage'),
                'date' => $request->input('date'),
                'payment_type' => PaymentType::PREMIUM_HIRE,
            ]),
            'callback_url' => (string) trim($request->input('redirect_url')),
        ];

        $paystackInstance = Paystack::getAuthorizationUrl($paymentDetails);

        return [
            'status' => 'success',
            'data' => $paystackInstance,
        ];
    }

    public function getPaymentRef($reference)
    {
        $paymentLog = PaymentLog::with('premiumHireBooking')
            ->where('reference', $reference)
            ->first();

        if(! $paymentLog) {
            return $this->error(null, 'Invalid payment reference', 400);
        }

        $data = (object) [
            'booking_id' => $paymentLog->premiumHireBooking?->uuid,
            'status' => $paymentLog->status,
        ];

        return $this->success($data, 'Payment reference fetched successfully');
    }

    public function userBookings($userId)
    {
        $user = User::with([
                'vehicle',
                'premiumHireBookingPassengers',
                'premiumHireBookings',
            ])
            ->findOrFail($userId);
        $bookings = $user->premiumHireBookings;

        $data = PremiumHireBookingResource::collection($bookings);
        return $this->success($data, "Bookings");
    }

    public function addPassenger($request)
    {
        $user = User::with('premiumHireBookingPassengers')
            ->findOrFail($request->user_id);

        if (!empty($request->passengers)) {
            foreach ($request->passengers as $passenger) {
                $user->premiumHireBookingPassengers()->create([
                    'premium_hire_booking_id' => $request->premium_hire_booking_id,
                    'name' => $passenger['name'],
                    'email' => $passenger['email'],
                    'phone_number' => $passenger['phone_number'],
                    'gender' => $passenger['gender'],
                    'next_of_kin' => $passenger['next_of_kin'],
                    'next_of_kin_phone_number' => $passenger['next_of_kin_phone_number'],
                ]);
            }
        } else {
            return $this->error("No valid passengers provided", 400);
        }

        return $this->success(null, "Passenger(s) added successfully");
    }

    public function getPassengers($userId)
    {
        $user = User::with('premiumHireBookingPassengers')
            ->findOrFail($userId);
        $passengers = $user->premiumHireBookingPassengers;

        $data = $passengers->map(function ($passenger) {
            return [
                'id' => $passenger->id,
                'name' => $passenger->name,
                'email' => $passenger->email,
                'phone_number' => $passenger->phone_number,
                'gender' => $passenger->gender,
                'next_of_kin' => $passenger->next_of_kin,
                'next_of_kin_phone_number' => $passenger->next_of_kin_phone_number,
            ];
        });

        return $this->success($data, "Passengers");
    }

    public function editPassenger($request)
    {
        $user = User::with([
                'premiumHireBookingPassengers',
                'premiumHireBookings'
            ])
            ->findOrFail($request->user_id);

        $premiumHireBooking = $user->premiumHireBookings->first();

        if (!$premiumHireBooking) {
            return $this->error('No premium hire booking found for this user.', 404);
        }

        if (!empty($request->passengers)) {
            foreach ($request->passengers as $passenger) {
                $user->premiumHireBookingPassengers()->updateOrCreate(
                    [
                        'premium_hire_booking_id' => $premiumHireBooking->id,
                        'name' => $passenger['name'],
                    ],
                    [
                        'email' => $passenger['email'] ?? null,
                        'phone_number' => $passenger['phone_number'],
                        'gender' => $passenger['gender'] ?? null,
                        'next_of_kin' => $passenger['next_of_kin'] ?? null,
                        'next_of_kin_phone_number' => $passenger['next_of_kin_phone_number'] ?? null,
                    ]
                );
            }
        }

        return $this->success(null, "Passenger(s) updated successfully");
    }

    public function deletePassenger($id)
    {
        $passenger = PremiumHireBookingPassenger::findOrFail($id);
        $passenger->delete();

        return $this->success(null, "Passenger deleted successfully");
    }

    public function cancelBooking($request)
    {
        $booking = PremiumHireBooking::findOrFail($request->id);
        $booking->update([
            'reason' => $request->reason,
            'status' => TripStatus::CANCELLED
        ]);

        return $this->success(null, "Booking cancelled successfully");
    }

    public function review($request)
    {
        $user = User::with('premiumHireRatings')
            ->findOrFail($request->user_id);

        $user->premiumHireRatings()->create([
            'rating' => $request->rating,
            'comment' => $request->comment,
        ]);

        return $this->success(null, "Review added successfully");
    }

    public function getReviews()
    {
        $reviews = PremiumHireRating::with('user')->get();
        $averageRating = $reviews->avg('rating');
        $ratingsCount = $reviews->groupBy('rating')->map->count();

        $reviewsList = $reviews->map(function ($review) {
            return [
                'name' => $review->user->first_name . ' ' . $review->user->last_name,
                'date' => $review->created_at->diffForHumans(),
                'comment' => $review->comment,
                'rating' => $review->rating,
            ];
        });

        $data = [
            'averageRating' => round($averageRating, 1),
            'totalRatings' => $reviews->count(),
            'ratingsCount' => [
                '5' => $ratingsCount[5] ?? 0,
                '4' => $ratingsCount[4] ?? 0,
                '3' => $ratingsCount[3] ?? 0,
                '2' => $ratingsCount[2] ?? 0,
                '1' => $ratingsCount[1] ?? 0,
            ],
            'reviews' => $reviewsList,
        ];

        return $this->success($data, "Reviews");
    }

    public function completedBookings($userId)
    {
        $bookings = PremiumHireBooking::with([
            'vehicle',
        ])
            ->where('user_id', $userId)
            ->where('status', TripStatus::COMPLETED)
            ->get();

        $data = $bookings->map(function ($booking) {
            return [
                'id' => $booking->id,
                'lng' => $booking->lng,
                'lat' => $booking->lat,
                'date' => $booking->date,
                'ticket_id' => $booking->uuid,
                'seat_number' => is_array($seats = $booking->vehicle?->seats) ? count($seats) : 0,
                'amount' => $booking->amount,
                'status' => $booking->status,
            ];
        });

        return $this->success($data, "Completed Bookings");
    }

    public function canceledBookings($userId)
    {
        $bookings = PremiumHireBooking::with([
            'vehicle',
        ])
            ->where('user_id', $userId)
            ->where('status', TripStatus::CANCELLED)
            ->get();

        $data = $bookings->map(function ($booking) {
            return [
                'id' => $booking->id,
                'lng' => $booking->lng,
                'lat' => $booking->lat,
                'date' => $booking->date,
                'ticket_id' => $booking->uuid,
                'seat_number' => is_array($seats = $booking->vehicle?->seats) ? count($seats) : 0,
                'amount' => $booking->amount,
                'status' => $booking->status,
            ];
        });

        return $this->success($data, "Cancelled Bookings");
    }

    public function upcomingBookings($userId)
    {
        $bookings = PremiumHireBooking::with([
            'vehicle',
        ])
            ->where('user_id', $userId)
            ->where('status', TripStatus::UPCOMING)
            ->get();

        $data = $bookings->map(function ($booking) {
            return [
                'id' => $booking->id,
                'lng' => $booking->lng,
                'lat' => $booking->lat,
                'date' => $booking->date,
                'ticket_id' => $booking->uuid,
                'seat_number' => is_array($seats = $booking->vehicle?->seats) ? count($seats) : 0,
                'amount' => $booking->amount,
                'status' => $booking->status,
            ];
        });

        return $this->success($data, "Upcoming Bookings");
    }

    public function bookingDetails($id)
    {
        $booking = PremiumHireBooking::with([
            'vehicle',
            'premiumHireBookingPassengers',
            'premiumHireBookings',
        ])
            ->findOrFail($id);

        $data = new PremiumHireBookingResource($booking);

        return $this->success($data, "Booking Details");
    }

    public function driverCompletedBookings($userId)
    {
        $bookings = PremiumHireBooking::with([
            'vehicle',
        ])
            ->where('driver_id', $userId)
            ->where('status', TripStatus::COMPLETED)
            ->get();

        $data = $bookings->map(function ($booking) {
            return [
                'id' => $booking->id,
                'lng' => $booking->lng,
                'lat' => $booking->lat,
                'date' => $booking->date,
                'ticket_id' => $booking->uuid,
                'seat_number' => is_array($seats = $booking->vehicle?->seats) ? count($seats) : 0,
                'amount' => $booking->amount,
                'status' => $booking->status,
            ];
        });

        return $this->success($data, "Completed Bookings");
    }

    public function driverCanceledBookings($userId)
    {
        $bookings = PremiumHireBooking::with([
            'vehicle',
        ])
            ->where('driver_id', $userId)
            ->where('status', TripStatus::CANCELLED)
            ->get();

        $data = $bookings->map(function ($booking) {
            return [
                'id' => $booking->id,
                'lng' => $booking->lng,
                'lat' => $booking->lat,
                'date' => $booking->date,
                'ticket_id' => $booking->uuid,
                'seat_number' => is_array($seats = $booking->vehicle?->seats) ? count($seats) : 0,
                'amount' => $booking->amount,
                'status' => $booking->status,
            ];
        });

        return $this->success($data, "Cancelled Bookings");
    }

    public function driverUpcomingBookings($userId)
    {
        $bookings = PremiumHireBooking::with([
            'vehicle',
        ])
            ->where('driver_id', $userId)
            ->where('status', TripStatus::UPCOMING)
            ->get();

        $data = $bookings->map(function ($booking) {
            return [
                'id' => $booking->id,
                'lng' => $booking->lng,
                'lat' => $booking->lat,
                'date' => $booking->date,
                'ticket_id' => $booking->uuid,
                'seat_number' => is_array($seats = $booking->vehicle?->seats) ? count($seats) : 0,
                'amount' => $booking->amount,
                'status' => $booking->status,
            ];
        });

        return $this->success($data, "Upcoming Bookings");
    }

    public function driverTripDetails($id)
    {
        $trip = PremiumHireBooking::with([
                'user',
                'vehicle',
                'premiumHireBookingPassengers',
            ])
            ->findOrFail($id);

        $data = new PremiumHireTripResource($trip);

        return $this->success($data, "Trip Detail");
    }

    public function acceptTrip($id)
    {
        $booking = PremiumHireBooking::findOrFail($id);
        $booking->update([
            'status' => TripStatus::ACCEPTED
        ]);

        return $this->success(null, "Trip accepted successfully");
    }

    public function startTrip($id)
    {
        $booking = PremiumHireBooking::findOrFail($id);
        $booking->update([
            'status' => TripStatus::INPROGRESS
        ]);

        return $this->success(null, "Trip started successfully");
    }

    public function cancelTrip($request)
    {
        $booking = PremiumHireBooking::findOrFail($request->id);
        $booking->update([
            'reason' => $request->reason,
            'status' => TripStatus::CANCELLED
        ]);

        return $this->success(null, "Booking cancelled successfully");
    }

    public function finishTrip($request)
    {
        $user = User::findOrFail($request->user_id);
        $booking = PremiumHireBooking::with(['premiumHireBookingPassengers', 'premiumHireManifests'])
            ->findOrFail($request->premium_hire_booking_id);

        if ($user->wallet < self::TRIP_CHARGE_AMOUNT) {
            return $this->error(null, "Insufficient wallet balance!", 400);
        }

        try {
            DB::beginTransaction();

            if ($booking->premiumHireBookingPassengers->isEmpty()) {
                return $this->error(null, "No passengers available!", 400);
            }

            foreach ($booking->premiumHireBookingPassengers as $passenger) {
                $manifest = new PremiumHireManifest([
                    'premium_hire_booking_id' => $booking->id,
                    'name' => $passenger->name,
                    'email' => $passenger->email,
                    'phone_number' => $passenger->phone_number,
                    'gender' => $passenger->gender,
                    'next_of_kin' => $passenger->next_of_kin,
                    'next_of_kin_phone_number' => $passenger->next_of_kin_phone_number,
                ]);

                $booking->premiumHireManifests()->save($manifest);
            }

            $this->topUpWallet($user);
            $this->chargeWallet($user);

            $booking->update(['status' => TripStatus::COMPLETED]);

            DB::commit();

            return $this->success(null, "Trip Started Successfully", 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error(null, "Failed to start trip: " . $e->getMessage(), 400);
        }
    }

}



