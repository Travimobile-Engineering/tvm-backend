<?php

namespace Database\Factories;

use App\Models\RouteSubregion;
use App\Models\TransitCompany;
use App\Models\User;
use App\Models\Vehicle\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Trip>
 */
class TripFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'uuid' => fake()->uuid(),
            'vehicle_id' => Vehicle::factory(),
            'transit_company_id' => TransitCompany::factory(),
            'departure' => RouteSubregion::factory(),
            'destination' => RouteSubregion::factory(),
            'price' => fake()->randomFloat(2, 1, 100),
            'bus_type' => fake()->randomElement(['bus', 'minibus']),
            'bus_stops' => [
                RouteSubregion::factory(),
                RouteSubregion::factory(),
                RouteSubregion::factory(),
            ],
        ];
    }
}
