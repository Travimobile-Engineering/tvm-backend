<?php

namespace Tests\Feature;

use App\Enum\TripStatus;
use App\Enum\TripType;
use App\Events\TripCreated;
use App\Models\RouteSubregion;
use App\Models\TransitCompany;
use App\Models\TransitCompanyUnion;
use App\Models\Trip;
use App\Models\TripBooking;
use App\Models\TripBookingPassenger;
use App\Models\User;
use App\Models\Vehicle\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class TripTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected array $headers;

    protected $transitCompany;

    protected $vehicle;

    protected $union;

    protected $state;

    protected $departureRegion;

    protected $destinationRegion;

    protected $busStop1;

    protected $busStop2;

    protected $busStop3;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->user->refresh();

        $this->union = TransitCompanyUnion::factory()->create();
        $routeSubregions = RouteSubregion::factory()->count(5)->create();

        $this->departureRegion = $routeSubregions[0];
        $this->destinationRegion = $routeSubregions[1];
        $this->busStop1 = $routeSubregions[2];
        $this->busStop2 = $routeSubregions[3];
        $this->busStop3 = $routeSubregions[4];

        $this->transitCompany = TransitCompany::factory()->create([
            'user_id' => $this->user->id,
            'union_id' => $this->union->id,
            'union_states_chapter' => $this->departureRegion->id,
        ]);
        $this->transitCompany->refresh();

        $this->vehicle = Vehicle::factory()->create([
            'user_id' => $this->user->id,
            'company_id' => $this->transitCompany->id,
        ]);
        $this->vehicle->refresh();

        $token = JWTAuth::fromUser($this->user);

        $this->headers = [
            'Authorization' => "Bearer {$token}",
            config('security.header_key', 'X-SECURE-AUTH') => config('security.header_value'),
        ];
    }

    #[Test]
    public function it_can_create_a_one_time_trip()
    {
        Event::fake([TripCreated::class]);

        $payload = [
            'user_id' => $this->user->id,
            'vehicle_id' => $this->vehicle->id,
            'transit_company_id' => $this->transitCompany->id,
            'departure_id' => $this->departureRegion->id,
            'destination_id' => $this->destinationRegion->id,
            'trip_duration' => 120,
            'departure_date' => now()->addDays(5)->toDateString(),
            'departure_time' => '10:00',
            'bus_type' => 'Luxury',
            'price' => 5000,
            'bus_stops' => [
                $this->busStop1->id,
                $this->busStop2->id,
                $this->busStop3->id,
            ],
            'means' => 1,
        ];

        $response = $this->postJson('/api/trip/driver/one-time', $payload, $this->headers);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Created successfully',
                'data' => [
                    'user_id' => $this->user->id,
                    'departure' => $this->departureRegion->id,
                    'destination' => $this->destinationRegion->id,
                    'type' => TripType::ONETIME,
                    'status' => TripStatus::UPCOMING,
                ],
            ]);

        $this->assertDatabaseHas('trips', [
            'user_id' => $this->user->id,
            'departure' => $this->departureRegion->id,
            'destination' => $this->destinationRegion->id,
            'type' => TripType::ONETIME,
            'status' => TripStatus::UPCOMING,
        ]);

        Event::assertDispatched(TripCreated::class);
    }

    #[Test]
    public function it_fails_when_departure_and_destination_are_the_same()
    {
        $payload = [
            'user_id' => $this->user->id,
            'vehicle_id' => $this->vehicle->id,
            'transit_company_id' => $this->transitCompany->id,
            'departure_id' => $this->destinationRegion->id,
            'destination_id' => $this->destinationRegion->id,
            'trip_duration' => 120,
            'departure_date' => now()->addDays(5)->toDateString(),
            'departure_time' => '10:00',
            'bus_type' => 'Standard',
            'bus_stops' => [
                $this->busStop1->id,
                $this->busStop2->id,
                $this->busStop3->id,
            ],
            'price' => 5000,
        ];

        $response = $this->postJson('/api/trip/driver/one-time', $payload, $this->headers);

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'Departure and destination cannot be the same',
            ]);
    }

    #[Test]
    public function it_can_create_recurring_trips()
    {
        $payload = [
            'user_id' => $this->user->id,
            'vehicle_id' => $this->vehicle->id,
            'transit_company_id' => $this->transitCompany->id,
            'departure_id' => $this->departureRegion->id,
            'destination_id' => $this->destinationRegion->id,
            'trip_duration' => 120,
            'start_date' => now()->addDays(2)->toDateString(),
            'reoccur_duration' => 2, // 2 months recurring
            'trip_days' => [
                [
                    'day' => now()->addDays(2)->format('D'), // today + 2 days (example: "Wed")
                    'time' => '09:00',
                ],
                [
                    'day' => now()->addDays(4)->format('D'), // today + 4 days (example: "Fri")
                    'time' => '14:00',
                ],
            ],
            'bus_type' => 'Standard',
            'price' => '6000',
            'bus_stops' => [
                $this->busStop1->id,
                $this->busStop2->id,
            ],
            'means' => 1,
        ];

        $response = $this->postJson('/api/trip/driver/recurring', $payload, $this->headers);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Recurring trips created successfully',
            ]);

        // Assert at least one trip exists in database
        $this->assertDatabaseHas('trips', [
            'user_id' => $this->user->id,
            'departure' => $this->departureRegion->id,
            'destination' => $this->destinationRegion->id,
            'type' => TripType::RECURRING,
            'status' => TripStatus::UPCOMING,
        ]);
    }

    #[Test]
    public function it_fails_to_create_recurring_trip_when_departure_and_destination_are_the_same()
    {
        $payload = [
            'user_id' => $this->user->id,
            'vehicle_id' => $this->vehicle->id,
            'transit_company_id' => $this->transitCompany->id,
            'departure_id' => $this->departureRegion->id,
            'destination_id' => $this->departureRegion->id,
            'trip_duration' => 120,
            'start_date' => now()->addDays(2)->toDateString(),
            'reoccur_duration' => 1,
            'trip_days' => [
                [
                    'day' => now()->addDays(2)->format('D'),
                    'time' => '10:00',
                ],
            ],
            'bus_type' => 'Standard',
            'price' => '5000',
            'bus_stops' => [
                $this->busStop1->id,
                $this->busStop2->id,
            ],
        ];

        $response = $this->postJson('/api/trip/driver/recurring', $payload, $this->headers);

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'Departure and destination cannot be the same',
            ]);
    }

    #[Test]
    public function it_can_cancel_a_trip()
    {
        $trip = Trip::factory()->create([
            'user_id' => $this->user->id,
            'vehicle_id' => $this->vehicle->id,
            'departure' => $this->departureRegion->id,
            'destination' => $this->destinationRegion->id,
            'status' => TripStatus::UPCOMING,
        ]);

        $payload = [
            'reason' => 'Driver unavailable',
        ];

        $response = $this->putJson("/api/trip/driver/cancel/{$trip->id}", $payload, $this->headers);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Trip Cancelled Successfully',
            ]);

        $this->assertDatabaseHas('trips', [
            'id' => $trip->id,
            'status' => TripStatus::CANCELLED,
            'reason' => 'Driver unavailable',
        ]);
    }

    #[Test]
    public function it_can_complete_a_trip()
    {
        $trip = Trip::factory()->create([
            'user_id' => $this->user->id,
            'vehicle_id' => $this->vehicle->id,
            'departure' => $this->departureRegion->id,
            'destination' => $this->destinationRegion->id,
            'status' => TripStatus::INPROGRESS,
        ]);

        $response = $this->putJson("/api/trip/driver/complete/{$trip->id}", [], $this->headers);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Trip Completed Successfully',
            ]);

        $this->assertDatabaseHas('trips', [
            'id' => $trip->id,
            'status' => TripStatus::COMPLETED,
        ]);
    }

    #[Test]
    public function it_can_start_a_trip()
    {
        $this->user->wallet = 10000;
        $this->user->save();

        $trip = Trip::factory()->create([
            'user_id' => $this->user->id,
            'vehicle_id' => $this->vehicle->id,
            'departure' => $this->departureRegion->id,
            'destination' => $this->destinationRegion->id,
            'status' => TripStatus::UPCOMING,
        ]);

        $tripBooking = TripBooking::factory()->create([
            'trip_id' => $trip->id,
            'payment_status' => 1,
        ]);

        TripBookingPassenger::factory()->create([
            'trip_booking_id' => $tripBooking->id,
            'on_seat' => true,
        ]);

        $payload = [
            'user_id' => $this->user->id,
            'trip_id' => $trip->id,
        ];

        $response = $this->postJson('/api/trip/driver/start-trip', $payload, $this->headers);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Trip Started Successfully',
            ]);

        $this->assertDatabaseHas('trips', [
            'id' => $trip->id,
            'status' => TripStatus::INPROGRESS,
        ]);
    }
}
