<?php

namespace Database\Factories;

use App\Models\TripBooking;
use App\Models\TripBookingPassenger;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TripBookingPassenger>
 */
class TripBookingPassengerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'trip_booking_id' => TripBooking::factory(),
            'name' => fake()->name(),
            'selected_seat' => fake()->randomElement(['A1', 'A2', 'B1', 'B2']),
        ];
    }
}
