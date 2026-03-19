<?php

namespace Database\Factories;

use App\Models\RouteRegion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RouteRegion>
 */
class RouteRegionFactory extends Factory
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
        ];
    }
}
