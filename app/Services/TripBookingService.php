<?php

namespace App\Services;

use App\Models\Trip;
use App\Models\User;
use App\Enum\TripStatus;
use App\Enum\PaymentMethod;
use App\Models\Transaction;
use App\Models\TripBooking;
use App\Models\TripPayment;
use App\Trait\HttpResponse;
use Illuminate\Support\Str;
use App\Models\Notification;
use App\Models\TransitCompany;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\QueryException;
use App\Http\Controllers\Payment\PaystackPaymentController;
use App\Http\Resources\TripBookingResource;

class TripBookingService
{
    use HttpResponse;

    protected $user;

    public function __construct(){
        $this->user = JWTAuth::user();
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store($request)
    {
        try{
            $payment_methods = ['wallet', 'paystack', 'transfer'];

            if(isset($request->amount_paid) && $request->amount_paid > 0){

                $amount_paid = $request->amount_paid;

                if(!isset($request->payment_method)) {
                    return['message' => 'Payment method is required', 'code' => 400];
                }

                if(!in_array($request->payment_method, $payment_methods)) {
                    return['message' => 'Invalid payment method', 'code' => 400];
                }

                if($request->payment_method == PaymentMethod::WALLET){

                    if(!isset($request->pin) || $request->pin != Auth::user()->txn_pin){
                        return ['message' => 'Invalid transaction pin', 'code' => 400];
                    }

                    if($amount_paid > $this->user->wallet) {
                        return['message' => 'You balance is insufficient to complete your request', 'code' => 400];
                    }

                    User::where('id', $this->user->id)->update(['wallet' => $this->user->wallet - $amount_paid]);

                    Transaction::create([
                        'title' => 'Bus ticket purchase',
                        'amount' => $amount_paid,
                        'type' => 'DR',
                    ]);
                }

                if($request->payment_method == PaymentMethod::PAYSTACK){
                    if(!isset($request->txn_reference)) {
                        return['message' => 'Transaction reference is required', 'code' => 400];
                    }

                    $txn_ref = $request->txn_reference;

                    $ppc = new PaystackPaymentController();

                    $response = $ppc->verifyTransaction($txn_ref, $amount_paid);

                    if($response['status'] == 'success'){
                        $trip = Trip::findOrFail($request->trip_id);

                        TripPayment::create([
                            'user_id' => $this->user->id,
                            'trip_id' => $request->trip_id,
                            'driver_id' => $trip->user_id,
                            'amount' => $amount_paid,
                            'status' => 'pending'
                        ]);

                        // Transaction::create([
                        //     'user_id' => $this->user->id,
                        //     'title' => 'Bus ticket purchase',
                        //     'amount' => $amount_paid,
                        //     'type' => 'DR',
                        //     'txn_reference' => $txn_ref
                        // ]);
                    }
                    else {
                        return['message' => $response, 'code' => 400];
                    }
                }
            }

            // Get the trip
            $trip = Trip::with(
                    [
                        'user.transitCompany',
                        'vehicle',
                        'tripBookings.user',
                        'departureRegion.state',
                        'destinationRegion.state',
                        'manifests'
                    ]
                )
                ->where('status', TripStatus::ACTIVE)
                ->find($request->trip_id);

            if(! $trip) {
                return $this->error(null, 'Invalid trip ID or trip is no longer available', 404);
            }

            $seats = $trip->vehicle?->seats;

            if (! is_array($seats)) {
                return $this->error(null, "Invalid seats data format", 400);
            }

            $departure = $trip->departureRegion?->state?->name . ' > ' . $trip->departureRegion?->name;
            $destination = $trip->destinationRegion?->state?->name . ' > ' . $trip->destinationRegion?->name;

            $transit_company = TransitCompany::where('id', $trip->transit_company_id)->first();

            //total number of seats in this vehicle
            $total_seats = count($seats ?? []);

            //get the total bookings for this trip
            $bookings = TripBooking::where('trip_id', $request->trip_id)->where('status', 1);
            // dd(count($bookings->get()));
            if(count($bookings->get()) >= $total_seats) {
                return['message' => 'Number of passengers for this trip already complete', 'code' => 400];
            }

            //get the already selected seats in the vehicle for this trip
            $selected_seats = $bookings->pluck('selected_seat')->toArray();

            if(!in_array(ucfirst($request->selected_seat), $seats)) {
                return['message' => 'Invalid seat selection', 'code' => 400];
            }

            if(in_array(ucfirst($request->selected_seat), $selected_seats)) {
                return['message' => 'Selected seat already taken', 'code' => 400];
            }

            $available_seats = array_filter($seats, function($seat) use ($selected_seats){
                return !in_array($seat, $selected_seats);
            });

            $trip->available_seats = $available_seats;

            do {
                $booking_id = strtoupper(Str::random(14));
            } while(TripBooking::where('booking_id', $booking_id)->exists());

            $booking = TripBooking::create([
                'booking_id' => $booking_id,
                'trip_id' => $request->trip_id,
                'user_id' => $this->user->id,
                'third_party_booking' => $request->third_party_booking ?? 0,
                'selected_seat' => ucfirst($request->selected_seat),
                'trip_type' => $request->trip_type,
                'travelling_with' => $request->travelling_with ?? null,
                'third_party_passenger_details' => $request->third_party_passenger_details ?? null,
                'amount_paid' => $request->amount_paid ?? 0,
                'payment_method' => $request->payment_method ?? '',
                'payment_status' => $request->payment_status ?? 0,
            ]);

            if($booking){
                Notification::create([
                    'user_id' => $this->user->id,
                    'title' => 'Booking Successful',
                    'description' => 'Your bus ticket to '.$destination.' on '.date("M jS Y h:iA",strtotime($trip->departure_at)).' has been successfully booked',
                    'additional_data' => json_encode([
                        'booking_id' => $booking_id,
                        'note' => 'Please arrive atleast 30 minutes early to ensure a smooth boarding experience.',
                        'help_desk' => 'If you have any questions or need assistance, feel free to contact our support team.',
                    ])
                ]);

                if(count($bookings->get()) >= $total_seats){
                    Trip::where('id', $request->trip_id)
                    ->update(['status' => TripStatus::ACTIVE]);

                    $trip = Trip::find($request->trip_id);
                }

                $booking->departure = $departure;
                $booking->destination = $destination;
                $booking->departure_at = $trip?->departure_at;
                $booking->estimated_arrival_at = $trip?->estimated_arrival_at;
                $booking->vehicle_detail = [
                    'name' => $trip->vehicle?->name,
                    'plate_no' => $trip->vehicle?->plate_no,
                ];

                $booking->company_detail = [
                    'name' => $transit_company->name,
                    'logo_url' => $transit_company->logo_url ?? null,
                ];

                $booking->user_detail = Auth::user();

                return $this->success($booking, 'Booking created successfully');
            }
        }
        catch(QueryException $e){
            if($e->getCode() === '23000'){
                return['message' => 'Integrity constraint violation: Cannot add or update a child row: a foreign key constraint fails', 'code' => 400];
            }
            else{
                Log::error($e->getMessage());
                return['message' => 'An error occured. Contact support', 'code' => 400];
            }
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($tripBooking)
    {
        if($tripBooking->user_id != $this->user->id) {
            return $this->error(null, 'You do not have the permission to complete this request', 400);
        }

        $booking = TripBooking::with([
                'trip.user',
                'user.transitCompany',
                'trip.departureRegion.state',
                'trip.destinationRegion.state',
                'trip.departureRegion.parks',
                'trip.destinationRegion.parks',
                'trip.vehicle',
            ])
            ->where('booking_id', $tripBooking->booking_id)
            ->first();

        if(!$booking) {
            return $this->error(null, 'Invalid booking ID', 400);
        }

        $data = new TripBookingResource($booking);

        return $this->success($data, 'Booking fetched successfully');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update($request, $tripBooking)
    {
        if($this->user->id != $tripBooking->user_id) {
            return $this->error(null, 'You do not have the permission to complete this request', 400);
        }

        $trip = Trip::where('uuid', $request->trip_id)
            ->where('status', 1)
            ->exists();

        if(!$trip) {
            return $this->error(null, 'Invalid booking ID', 400);
        }

        $tripBooking->update([
            'trip_id' => $request->trip_id,
            'selected_seat' => ucfirst($request->selected_seat),
            'trip_type' => $request->trip_type,
            'travelling_with' => $request->travelling_with ?? '',
            'amount_paid' => $request->amount_paid ?? 0,
            'payment_method' => $request->payment_method ?? '',
            'payment_status' => $request->payment_status
        ]);

        return $this->success($tripBooking, 'Booking updated successfully');
    }

    public function cancelTripBooking($request)
    {
        $booking = TripBooking::where('booking_id', $request->booking_id)->firstOrFail();

        if($this->user->id != $booking->user_id) {
            return $this->error(null, 'You do not have the permission to complete this request', 400);
        }

        $booking->update(['status' => 0]);

        return $this->success($booking, 'Booking cancelled successfully');
    }

    // Old version (Not optimized & not used anymore)
    public function getUserTripBookingHistory($request){
        $user_id = $request->user;
        $is_email = filter_var($request->user, FILTER_VALIDATE_EMAIL) ? true : false;

        if($is_email){
            $user = User::where('email', $request->user)->select('id')->get()->first();
            $user_id = $user->id;
        }

        if($this->user->id != $user_id) return['message' => 'You do not have the permission to complete this request', 'code' => 400];

        $history = TripBooking::with('trip')->where('user_id', $user_id)->get();
        $hty = [];
        foreach($history as $key => $item){
            foreach($item->toArray() as $k => $value){
                if($k != 'trip') $hty[$key][$k] = $value;
                else{
                    $departure_town = DB::table('route_subregions')->where('id', $history[$key][$k]['departure'])->first();
                    $departure_state = DB::table('states')->where('id', $departure_town->state_id)->first();
                    $destination_town = DB::table('route_subregions')->where('id', $history[$key][$k]['destination'])->first();
                    $destination_state = DB::table('states')->where('id', $destination_town->state_id)->first();

                    $hty[$key][$k]['departure'] = $departure_state->name.' > '.$departure_town->name;
                    $hty[$key][$k]['destination'] = $destination_state->name.' > '.$destination_town->name;
                    $hty[$key][$k]['departure_at'] = $history[$key][$k]['departure_at'];
                    $hty[$key][$k]['estimated_arrival_at'] = $history[$key][$k]['estimated_arrival_at'];
                }

            }
        }
        return['data' => $hty];
    }

    // New version, optimized and shorter
    public function userBookingHistory($request)
    {
        $user = User::findOrFail($request->user);
        $history = TripBooking::with([
            'trip' => function ($query) {
                $query->select('id', 'departure', 'destination', 'departure_date', 'trip_duration');
            },
        ])
            ->where('user_id', $user->id)
            ->get();

        return $this->success($history, 'Booking History Fetched Successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TripBooking $tripBooking)
    {
        //
        //
    }
}
