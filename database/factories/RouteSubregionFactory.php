<?php

namespace Database\Factories;

use App\Models\RouteRegion;
use App\Models\RouteSubregion;
use App\Models\State;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RouteSubregion>
 */
class RouteSubregionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word(),
            'region_id' => RouteRegion::factory(),
            'state_id' => State::factory(),
            'status' => 1,
        ];
    }
}
